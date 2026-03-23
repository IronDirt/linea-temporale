<?php
declare(strict_types=1);

function timeline_storage_dir(): string
{
	return __DIR__ . '/shared-timelines';
}

function timeline_base_url(): string
{
	$scheme = 'http';
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
		$scheme = 'https';
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		$scheme = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0] === 'https' ? 'https' : $scheme;
	}

	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$path = $_SERVER['PHP_SELF'] ?? '/index.php';

	return $scheme . '://' . $host . $path;
}

function timeline_generate_id(int $bytes = 6): string
{
	return bin2hex(random_bytes($bytes));
}

function timeline_build_share_urls(string $timelineId, string $adminToken, string $viewerToken): array
{
	$baseUrl = timeline_base_url();

	return [
		'adminUrl' => $baseUrl . '?timeline=' . rawurlencode($timelineId) . '&admin=' . rawurlencode($adminToken),
		'viewerUrl' => $baseUrl . '?timeline=' . rawurlencode($timelineId) . '&view=' . rawurlencode($viewerToken)
	];
}

$storageDir = timeline_storage_dir();
if (!is_dir($storageDir)) {
	mkdir($storageDir, 0775, true);
}

if (($_GET['api'] ?? '') === 'delete' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	header('Content-Type: application/json; charset=utf-8');

	try {
		$rawBody = file_get_contents('php://input');
		$payload = json_decode($rawBody ?: '{}', true, 512, JSON_THROW_ON_ERROR);

		$timelineId = isset($payload['timelineId']) && is_string($payload['timelineId'])
			? trim($payload['timelineId'])
			: '';
		$adminToken = isset($payload['adminToken']) && is_string($payload['adminToken'])
			? trim($payload['adminToken'])
			: '';

		if ($timelineId === '' || preg_match('/^[a-f0-9]{12}$/', $timelineId) !== 1) {
			http_response_code(400);
			echo json_encode(['ok' => false, 'message' => 'ID timeline non valido.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		}

		$filePath = $storageDir . '/' . $timelineId . '.json';

		if (!is_file($filePath)) {
			http_response_code(404);
			echo json_encode(['ok' => false, 'message' => 'Timeline non trovata.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		}

		$content = file_get_contents($filePath);
		$decoded = json_decode($content ?: '{}', true);

		if (!is_array($decoded) || !isset($decoded['adminToken']) || !hash_equals((string) $decoded['adminToken'], $adminToken)) {
			http_response_code(403);
			echo json_encode(['ok' => false, 'message' => 'Token admin non valido.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		}

		unlink($filePath);

		echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	} catch (Throwable $error) {
		http_response_code(500);
		echo json_encode(['ok' => false, 'message' => 'Errore durante la cancellazione online.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
}

if (($_GET['api'] ?? '') === 'save' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	header('Content-Type: application/json; charset=utf-8');

	try {
		$rawBody = file_get_contents('php://input');
		$payload = json_decode($rawBody ?: '{}', true, 512, JSON_THROW_ON_ERROR);

		$events = $payload['events'] ?? null;
		$title = isset($payload['title']) && is_string($payload['title']) ? trim($payload['title']) : 'Timeline';
		$timelineId = isset($payload['timelineId']) && is_string($payload['timelineId'])
			? trim($payload['timelineId'])
			: '';
		$adminToken = isset($payload['adminToken']) && is_string($payload['adminToken'])
			? trim($payload['adminToken'])
			: '';

		if (!is_array($events)) {
			http_response_code(400);
			echo json_encode([
				'ok' => false,
				'message' => 'Payload non valido: eventi mancanti.'
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		}

		$existingData = null;
		$isUpdate = false;
		if ($timelineId !== '' && preg_match('/^[a-f0-9]{12}$/', $timelineId) === 1) {
			$filePath = $storageDir . '/' . $timelineId . '.json';
			if (is_file($filePath)) {
				$content = file_get_contents($filePath);
				$decoded = json_decode($content ?: '{}', true);
				if (is_array($decoded) && isset($decoded['adminToken']) && hash_equals((string) $decoded['adminToken'], $adminToken)) {
					$existingData = $decoded;
					$isUpdate = true;
				}
			}
		}

		if (!$isUpdate) {
			do {
				$timelineId = timeline_generate_id(6);
				$filePath = $storageDir . '/' . $timelineId . '.json';
			} while (is_file($filePath));

			$adminToken = timeline_generate_id(16);
			$viewerToken = timeline_generate_id(16);
		} else {
			$filePath = $storageDir . '/' . $timelineId . '.json';
			$adminToken = (string) ($existingData['adminToken'] ?? '');
			$viewerToken = (string) ($existingData['viewerToken'] ?? '');
		}

		$record = [
			'updatedAt' => date(DATE_ATOM),
			'title' => $title !== '' ? $title : 'Timeline',
			'events' => array_values($events),
			'adminToken' => $adminToken,
			'viewerToken' => $viewerToken,
			'version' => 1
		];

		file_put_contents($filePath, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

		$urls = timeline_build_share_urls($timelineId, $adminToken, $viewerToken);

		echo json_encode([
			'ok' => true,
			'timelineId' => $timelineId,
			'adminToken' => $adminToken,
			'viewerToken' => $viewerToken,
			'adminUrl' => $urls['adminUrl'],
			'viewerUrl' => $urls['viewerUrl']
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	} catch (Throwable $error) {
		http_response_code(500);
		echo json_encode([
			'ok' => false,
			'message' => 'Errore durante il salvataggio online.'
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
}

$appMode = 'editor';
$sharedPayload = null;

$timelineQueryId = isset($_GET['timeline']) && is_string($_GET['timeline']) ? trim($_GET['timeline']) : '';
$adminQueryToken = isset($_GET['admin']) && is_string($_GET['admin']) ? trim($_GET['admin']) : '';
$viewerQueryToken = isset($_GET['view']) && is_string($_GET['view']) ? trim($_GET['view']) : '';

if ($timelineQueryId !== '' && preg_match('/^[a-f0-9]{12}$/', $timelineQueryId) === 1) {
	$filePath = $storageDir . '/' . $timelineQueryId . '.json';
	if (is_file($filePath)) {
		$content = file_get_contents($filePath);
		$decoded = json_decode($content ?: '{}', true);

		if (is_array($decoded)) {
			$storedAdmin = (string) ($decoded['adminToken'] ?? '');
			$storedViewer = (string) ($decoded['viewerToken'] ?? '');
			$canEdit = $adminQueryToken !== '' && $storedAdmin !== '' && hash_equals($storedAdmin, $adminQueryToken);
			$canView = $viewerQueryToken !== '' && $storedViewer !== '' && hash_equals($storedViewer, $viewerQueryToken);

			if ($canEdit || $canView) {
				$appMode = $canView && !$canEdit ? 'viewer' : 'editor';
				$urls = timeline_build_share_urls($timelineQueryId, $storedAdmin, $storedViewer);
				$sharedPayload = [
					'timelineId' => $timelineQueryId,
					'adminToken' => $canEdit ? $storedAdmin : null,
					'viewerToken' => $canEdit ? $storedViewer : null,
					'adminUrl' => $canEdit ? $urls['adminUrl'] : '',
					'viewerUrl' => $urls['viewerUrl'],
					'title' => isset($decoded['title']) && is_string($decoded['title']) ? $decoded['title'] : 'Timeline',
					'events' => isset($decoded['events']) && is_array($decoded['events']) ? $decoded['events'] : []
				];
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<title>Timeline SalernoHub | Crea Linee Temporali Personalizzate Online</title>
	<meta name="description" content="Crea una linea temporale personalizzata online per scuola, religione, lavoro, studio, storia, progetti ed eventi personali. Timeline semplice, veloce e gratuita.">
	<meta name="keywords" content="linea temporale, timeline online, creare timeline, timeline scuola, timeline religione, timeline lavoro, linea del tempo personalizzata, timeline storia, timeline progetti, timeline eventi">
	<meta name="author" content="SalernoHub">
	<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
	<meta name="googlebot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
	<meta name="bingbot" content="index, follow">
	<link rel="canonical" href="https://timeline.salernohub.net/">
	<link rel="alternate" href="https://timeline.salernohub.net/" hreflang="it-IT">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=en" hreflang="en">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=es" hreflang="es">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=de" hreflang="de">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=fr" hreflang="fr">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=pt" hreflang="pt">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=ru" hreflang="ru">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=tr" hreflang="tr">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=ja" hreflang="ja">
	<link rel="alternate" href="https://timeline.salernohub.net/?lang=zh" hreflang="zh">
	<meta property="og:type" content="website">
	<meta property="og:locale" content="it_IT">
	<meta property="og:site_name" content="Timeline SalernoHub">
	<meta property="og:title" content="Timeline SalernoHub | Crea Linee Temporali Personalizzate Online">
	<meta property="og:description" content="Strumento online per creare linee temporali personalizzate per scuola, religione, lavoro, studio, storia e progetti.">
	<meta property="og:url" content="https://timeline.salernohub.net/">
	<meta property="og:image" content="https://timeline.salernohub.net/og-image.svg">
	<meta property="og:image:secure_url" content="https://timeline.salernohub.net/og-image.svg">
	<meta property="og:image:type" content="image/svg+xml">
	<meta property="og:image:width" content="1200">
	<meta property="og:image:height" content="630">
	<meta property="og:image:alt" content="Timeline SalernoHub - Crea linee temporali personalizzate online">
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="Timeline SalernoHub | Crea Linee Temporali Personalizzate Online">
	<meta name="twitter:description" content="Crea la tua linea del tempo personalizzata in modo semplice e veloce.">
	<meta name="twitter:image" content="https://timeline.salernohub.net/og-image.svg">
	<meta name="application-name" content="Timeline SalernoHub">
	<meta name="theme-color" content="#e9e5de" id="meta-theme-color">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<script>
		(function() {
			const savedTheme = localStorage.getItem('timeline_theme_v1');
			const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			const theme = savedTheme || (prefersDark ? 'dark' : 'light');
			const color = (theme === 'dark') ? '#0b1220' : '#e9e5de';
			document.documentElement.classList.toggle('theme-dark', theme === 'dark');
			document.addEventListener('DOMContentLoaded', () => {
				document.body.classList.toggle('theme-dark', theme === 'dark');
				const meta = document.getElementById('meta-theme-color');
				if (meta) meta.setAttribute('content', color);
			});
		})();
	</script>
	<meta name="referrer" content="strict-origin-when-cross-origin">
		<!-- Google tag (gtag.js) -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=G-R2QSCDWV2Q"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', 'G-R2QSCDWV2Q');
		</script>
	<link rel="alternate" href="https://timeline.salernohub.net/" hreflang="it-IT">
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "WebSite",
		"name": "Timeline SalernoHub",
		"url": "https://timeline.salernohub.net/",
		"inLanguage": "it-IT",
		"description": "Applicazione web per creare linee temporali personalizzate per scuola, religione, lavoro, studio, storia e progetti personali.",
		"potentialAction": {
			"@type": "SearchAction",
			"target": "https://timeline.salernohub.net/",
			"query-input": "required name=timeline"
		}
	}
	</script>
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "SoftwareApplication",
		"name": "Timeline SalernoHub",
		"applicationCategory": "EducationalApplication",
		"operatingSystem": "Web",
		"url": "https://timeline.salernohub.net/",
		"inLanguage": "it-IT",
		"offers": {
			"@type": "Offer",
			"price": "0",
			"priceCurrency": "EUR"
		},
		"description": "Generatore di linee temporali online per organizzare eventi in ambito scolastico, religioso, lavorativo, universitario, storico, sportivo, medico e personale.",
		"featureList": [
			"Creazione di eventi con data, titolo e descrizione",
			"Aggiunta immagini agli eventi",
			"Esportazione e importazione JSON",
			"Modalità schermo intero per presentazioni",
			"Personalizzazione del titolo timeline"
		]
	}
	</script>
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "FAQPage",
		"mainEntity": [
			{
				"@type": "Question",
				"name": "A cosa serve Timeline SalernoHub?",
				"acceptedAnswer": {
					"@type": "Answer",
					"text": "Timeline SalernoHub permette di creare linee temporali personalizzate online con data, titolo, descrizione e immagini per organizzare eventi in modo chiaro."
				}
			},
			{
				"@type": "Question",
				"name": "Per quali ambiti posso usare questa timeline?",
				"acceptedAnswer": {
					"@type": "Answer",
					"text": "Puoi usarla per scuola, religione, lavoro, università, studio della storia, progetti personali, eventi familiari, sport e pianificazione attività."
				}
			},
			{
				"@type": "Question",
				"name": "I dati restano privati nel browser?",
				"acceptedAnswer": {
					"@type": "Answer",
					"text": "Gli eventi vengono salvati localmente nel browser e puoi esportarli o importarli con file JSON."
				}
			}
		]
	}
	</script>
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="style-mobile.css" media="(max-width: 900px)">
	<link rel="stylesheet" href="style-desktop.css" media="(min-width: 901px)">
	<!-- PWA: Manifest -->
	<link rel="manifest" href="manifest.json">
</head>
<body>
	<div class="lang-menu-wrap">
		<button type="button" id="langToggleBtn" class="lang-toggle-btn" aria-label="Switch language" title="Switch language">EN</button>
		<div id="langMenu" class="lang-menu hidden">
			<button type="button" class="lang-menu-btn" data-lang="it">Italiano</button>
			<button type="button" class="lang-menu-btn" data-lang="en">English</button>
			<button type="button" class="lang-menu-btn" data-lang="es">Español</button>
			<button type="button" class="lang-menu-btn" data-lang="de">Deutsch</button>
			<button type="button" class="lang-menu-btn" data-lang="fr">Français</button>
			<button type="button" class="lang-menu-btn" data-lang="pt">Português</button>
			<button type="button" class="lang-menu-btn" data-lang="ru">Русский</button>
			<button type="button" class="lang-menu-btn" data-lang="tr">Türkçe</button>
			<button type="button" class="lang-menu-btn" data-lang="ja">日本語</button>
			<button type="button" class="lang-menu-btn" data-lang="zh">中文</button>
		</div>
	</div>
	<div class="container">
		<div class="top-content">
			<h1 data-i18n="appTitle">Linea Temporale</h1>
			<div class="fab-stack top-actions">
				<button type="button" class="fab-add" id="openFormBtn" aria-label="Aggiungi nuovo evento">
					<svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
				</button>
				<div class="backup-wrap">
					<button type="button" class="fab-backup" id="backupMenuBtn" aria-label="Backup" title="Backup">
						<svg class="backup-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M12 13v8M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242M8 17l4 4 4-4"></path>
						</svg>
					</button>
					<div class="backup-menu hidden" id="backupMenu" role="menu" aria-label="Menu backup">
						<div class="online-save-panel" id="onlineSavePanel">
							<div class="online-save-field">
								<label data-i18n="adminLinkLabel" for="adminLinkInput">Link admin</label>
								<div class="online-save-input-row">
									<input id="adminLinkInput" type="text" readonly placeholder="Link admin (vuoto finché non salvi)">
									<button type="button" class="secondary copy-link-btn" id="copyAdminLinkBtn" aria-label="Copia link admin" title="Copia link admin">
										<svg class="icon" viewBox="0 0 24 24">
											<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
											<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
										</svg>
									</button>
								</div>
							</div>
							<div class="online-save-field">
								<label data-i18n="viewerLinkLabel" for="viewerLinkInput">Link solo visualizzatore</label>
								<div class="online-save-input-row">
									<input id="viewerLinkInput" type="text" readonly placeholder="Link pubblico (vuoto finché non salvi)">
									<button type="button" class="secondary copy-link-btn" id="copyViewerLinkBtn" aria-label="Copia link visualizzatore" title="Copia link visualizzatore">
										<svg class="icon" viewBox="0 0 24 24">
											<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
											<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
										</svg>
									</button>
								</div>
							</div>
							<div class="backup-actions-row">
								<button type="button" class="secondary" id="downloadBtn" role="menuitem">
									<svg class="icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5l5 5 5-5m-5 5V3"></path></svg>
									<span data-i18n="downloadBtnText">Scarica</span>
								</button>
								<button type="button" class="secondary" id="uploadBtn" role="menuitem">
									<svg class="icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5m5-5v12"></path></svg>
									<span data-i18n="importBtnText">Importa</span>
								</button>
								<button type="button" class="primary" id="saveOnlineBtn" role="menuitem">
									<svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
									<span data-i18n="saveBtnText">Salva</span>
								</button>
							</div>
						</div>
					</div>
				</div>
				<button type="button" class="fab-fullscreen" id="fullscreenBtn" aria-label="Attiva schermo intero" title="Attiva schermo intero">
					<svg class="fullscreen-icon" id="fullscreenEnterIcon" viewBox="0 0 24 24">
						<path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
					</svg>
					<svg class="fullscreen-icon hidden" id="fullscreenExitIcon" viewBox="0 0 24 24">
						<path d="M4 14h6m0 0v6m0-6L3 21m17-7h-6m0 0v6m0-6l7 7M4 10h6m0 0V4m0 6L3 3m17 7h-6m0 0V4m0 6l7-7"></path>
					</svg>
				</button>
				<button type="button" class="fab-theme" id="themeToggleBtn" aria-label="Tema scuro" title="Tema scuro">
					<svg class="theme-icon theme-icon-moon" id="themeMoonIcon" viewBox="0 0 24 24">
						<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
					</svg>
					<svg class="theme-icon theme-icon-sun hidden" id="themeSunIcon" viewBox="0 0 24 24">
						<circle cx="12" cy="12" r="5"></circle>
						<path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M17.66 6.34l1.42-1.42"></path>
					</svg>
				</button>
				<button type="button" class="muted timeline-reset-btn fab-reset hidden" id="resetTimelineBtn" aria-label="Crea nuova linea temporale locale" title="Nuova linea temporale (solo locale)">
					<svg class="timeline-reset-icon" viewBox="0 0 24 24">
						<path d="M3 6h18m-2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-6 5v6m4-6v6"></path>
					</svg>
				</button>
				<input id="uploadInput" class="hidden" type="file" accept="application/json">
			</div>
		</div>

		<section class="card timeline-section">
			<div class="timeline-topbar">
				<div class="timeline-header">
					<h2 id="timelineTitle">Timeline</h2>
					<button type="button" class="muted timeline-title-edit" id="editTimelineTitleBtn" aria-label="Modifica titolo timeline" title="Modifica titolo">
						<svg class="icon" viewBox="0 0 24 24"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
					</button>
				</div>
				<div class="timeline-right-controls">
					<div class="viewer-actions hidden" id="viewerActions" aria-label="Azioni visualizzatore">
						<button type="button" class="primary viewer-action-btn" id="viewerCreateBtn">
							<svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
							<span data-i18n="viewerCreateBtnText">Crea la tua linea temporale</span>
						</button>
						<button type="button" class="secondary viewer-action-btn viewer-icon-btn" id="viewerDownloadBtn" aria-label="Scarica timeline" title="Scarica timeline">
							<svg class="viewer-action-icon" viewBox="0 0 24 24">
								<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5l5 5 5-5m-5 5V3"></path>
							</svg>
						</button>
						<button type="button" class="secondary viewer-action-btn viewer-icon-btn" id="viewerFullscreenBtn" aria-label="Attiva schermo intero" title="Attiva schermo intero">
							<svg class="viewer-action-icon" id="viewerFullscreenEnterIcon" viewBox="0 0 24 24">
								<path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
							</svg>
							<svg class="viewer-action-icon hidden" id="viewerFullscreenExitIcon" viewBox="0 0 24 24">
								<path d="M4 14h6m0 0v6m0-6L3 21m17-7h-6m0 0v6m0-6l7 7M4 10h6m0 0V4m0 6L3 3m17 7h-6m0 0V4m0 6l7-7"></path>
							</svg>
						</button>
					</div>
						<div class="timeline-zoom-controls" data-i18n-aria-label="zoomControlsLabel" aria-label="Controlli zoom timeline">
						<button type="button" class="muted timeline-zoom-btn" id="zoomOutBtn" aria-label="Riduci dettagli timeline" title="Riduci dettagli">
							<svg class="icon" viewBox="0 0 24 24"><path d="M5 12h14"></path></svg>
						</button>
						<button type="button" class="muted timeline-zoom-btn" id="zoomInBtn" aria-label="Aumenta dettagli timeline" title="Aumenta dettagli">
							<svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
						</button>
					</div>
				</div>
			</div>
			<div id="timeline" class="timeline"></div>
		</section>
		<footer class="site-footer">
			<p>&copy; <span id="copyrightYear"></span> <a href="https://timeline.salernohub.net" rel="noopener">timeline.salernohub.net</a></p>
		</footer>
	</div>

	<div class="modal hidden" id="localResetModal" role="dialog" aria-modal="true" aria-labelledby="localResetTitle">
		<div class="modal-backdrop" id="localResetBackdrop"></div>
		<section class="modal-card local-reset-card">
			<div class="modal-header">
					<h2 id="localResetTitle">Nuova linea temporale</h2>
				<button type="button" class="muted close-btn" id="closeLocalResetBtn" aria-label="Chiudi">
					<svg class="icon" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"></path></svg>
				</button>
			</div>
			<p class="local-reset-text" data-i18n="localResetText1">Questa azione cancellerà la timeline solo in locale (dispositivo/browser attuale).</p>
			<p class="local-reset-text" data-i18n="localResetText2">La versione online non verrà eliminata.</p>
			<p class="local-reset-text" data-i18n="localResetText3">Prima di procedere, si consiglia di scaricare la linea temporale o salvarla online.</p>
			<div class="local-reset-actions">
				<button type="button" class="secondary" id="localResetDownloadBtn">
					<svg class="icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5l5 5 5-5m-5 5V3"></path></svg>
					<span data-i18n="localResetDownloadText">Scarica</span>
				</button>
				<button type="button" class="secondary" id="localResetSaveOnlineBtn">
					<svg class="icon" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					<span data-i18n="localResetSaveOnlineText">Salva online</span>
				</button>
				<button type="button" class="danger" id="localResetConfirmBtn">
					<svg class="icon" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"></path></svg>
					<span data-i18n="localResetConfirmText">Cancella solo locale</span>
				</button>
				<button type="button" class="danger" id="localResetDeleteOnlineBtn">
					<svg class="icon" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-6 5v6m4-6v6"></path></svg>
					<span data-i18n="localResetDeleteOnlineText">Cancella online</span>
				</button>
			</div>
			<div class="local-reset-online-panel online-save-field hidden" id="localResetAdminLinkWrap">
				<label data-i18n="localResetAdminLinkLabel" for="localResetAdminLinkInput">Link admin</label>
				<div class="online-save-input-row">
					<input type="text" id="localResetAdminLinkInput" readonly placeholder="Salva online per ottenere il link admin">
					<button type="button" class="muted copy-link-btn" id="localResetCopyAdminLinkBtn" aria-label="Copia link admin" title="Copia link admin">
						<svg class="icon" viewBox="0 0 24 24">
							<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
							<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
						</svg>
					</button>
				</div>
			</div>
		</section>
	</div>

	<div class="mobile-viewer-menu-wrap hidden" id="mobileViewerMenuWrap" aria-label="Azioni visualizzatore mobile">
		<button type="button" class="primary mobile-viewer-action-btn mobile-viewer-create-btn hidden" id="mobileViewerCreateBtn">
			<svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
			<span data-i18n="viewerCreateBtnText">Crea la tua linea temporale</span>
		</button>
		<div class="mobile-viewer-menu hidden" id="mobileViewerMenu">
			<button type="button" class="muted mobile-viewer-icon-btn" id="mobileViewerZoomInBtn" aria-label="Aumenta dettagli timeline" title="Aumenta dettagli">
				<svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
			</button>
			<button type="button" class="muted mobile-viewer-icon-btn" id="mobileViewerZoomOutBtn" aria-label="Riduci dettagli timeline" title="Riduci dettagli">
				<svg class="icon" viewBox="0 0 24 24"><path d="M5 12h14"></path></svg>
			</button>
			<button type="button" class="secondary mobile-viewer-icon-btn" id="mobileViewerThemeBtn" aria-label="Tema scuro" title="Tema scuro">
				<svg class="viewer-action-icon" id="mobileViewerThemeMoonIcon" viewBox="0 0 24 24">
					<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
				</svg>
				<svg class="viewer-action-icon hidden" id="mobileViewerThemeSunIcon" viewBox="0 0 24 24">
					<circle cx="12" cy="12" r="5"></circle>
					<path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M17.66 6.34l1.42-1.42"></path>
				</svg>
			</button>
			<button type="button" class="secondary mobile-viewer-icon-btn" id="mobileViewerFullscreenBtn" aria-label="Attiva schermo intero" title="Attiva schermo intero">
				<svg class="viewer-action-icon" id="mobileViewerFullscreenEnterIcon" viewBox="0 0 24 24">
					<path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
				</svg>
				<svg class="viewer-action-icon hidden" id="mobileViewerFullscreenExitIcon" viewBox="0 0 24 24">
					<path d="M4 14h6m0 0v6m0-6L3 21m17-7h-6m0 0v6m0-6l7 7M4 10h6m0 0V4m0 6L3 3m17 7h-6m0 0V4m0 6l7-7"></path>
				</svg>
			</button>
			<button type="button" class="secondary mobile-viewer-icon-btn" id="mobileViewerDownloadBtn" aria-label="Scarica timeline" title="Scarica timeline">
				<svg class="viewer-action-icon" viewBox="0 0 24 24">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5l5 5 5-5m-5 5V3"></path>
				</svg>
			</button>
		</div>
		<button type="button" class="mobile-viewer-menu-btn" id="mobileViewerMenuBtn" aria-label="Apri azioni visualizzatore" title="Azioni visualizzatore">
			<svg class="backup-icon" id="mobileViewerMenuOpenIcon" viewBox="0 0 24 24">
				<path d="M3 12h18M3 6h18M3 18h18"></path>
			</svg>
			<svg class="backup-icon hidden" id="mobileViewerMenuCloseIcon" viewBox="0 0 24 24">
				<path d="M18 6L6 18M6 6l12 12"></path>
			</svg>
		</button>
	</div>

	<div class="modal hidden" id="eventModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
		<div class="modal-backdrop" id="modalBackdrop"></div>
		<section class="modal-card">
			<div class="modal-header">
				<h2 id="modalTitle">Nuovo evento</h2>
				<button type="button" class="muted close-btn" id="closeModalBtn" aria-label="Chiudi">
					<svg class="icon" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"></path></svg>
				</button>
			</div>

			<form id="eventForm">
				<input type="hidden" id="editIndex" value="-1">

				<label data-i18n="dateLabel" for="eventDate">Data</label>
				<input id="eventDate" type="date" required>
				<div class="date-visibility-options">
					<label class="checkbox-inline" for="eventShowDay">
						<input id="eventShowDay" type="checkbox" checked>
						<span data-i18n="showDayText">Mostra giorno</span>
					</label>
					<label class="checkbox-inline" for="eventShowMonth">
						<input id="eventShowMonth" type="checkbox" checked>
						<span data-i18n="showMonthText">Mostra mese</span>
					</label>
				</div>
				<div class="date-visibility-options">
					<label class="checkbox-inline" for="eventUseCustomYear">
						<input id="eventUseCustomYear" type="checkbox">
						<span data-i18n="useCustomYearText">Usa anno personalizzato</span>
					</label>
					<input id="eventCustomYear" class="year-input hidden" type="number" step="1" placeholder="Es. -500">
				</div>

				<label data-i18n="eraTagLabel" for="eventEraTag">Targhetta era</label>
				<select id="eventEraTag">
					<option value="none" data-i18n="eraNoneOption">Nessuna</option>
					<option value="christian" data-i18n="eraChristianOption">a.C. / d.C.</option>
					<option value="common-era" data-i18n="eraCommonEraOption">a.E.V. / E.V.</option>
				</select>

				<label data-i18n="titleLabel" for="eventTitle">Titolo</label>
				<input id="eventTitle" type="text" placeholder="Es. Inizio progetto" required>

				<label data-i18n="textLabel" for="eventText">Testo</label>
				<textarea id="eventText" placeholder="Descrizione evento" required></textarea>

				<label data-i18n="imageLabel" for="eventImage">Immagine (opzionale)</label>
				<div class="image-picker-row">
					<label id="eventImageTrigger" for="eventImage" class="button-like secondary image-picker-btn">
						<svg class="icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5l5 5 5-5m-5 5V3"></path></svg>
						<span data-i18n="chooseImageText">Scegli immagine</span>
					</label>
					<button type="button" id="removeEventImageBtn" class="muted image-remove-btn hidden">
						<svg class="icon" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
						<span data-i18n="removeImageText">Rimuovi immagine</span>
					</button>
					<span id="eventImageName" class="image-picker-name">Nessun file selezionato</span>
				</div>
				<input id="eventImage" class="hidden" type="file" accept="image/*">
				<div id="eventImagePreviewWrap" class="image-preview-wrap hidden">
					<img id="eventImagePreview" class="image-preview" src="" alt="Anteprima immagine evento">
				</div>

				<div class="form-actions">
					<button type="submit" class="primary" id="saveEventBtn">
						<svg class="icon" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
						<span id="saveEventBtnText" data-i18n="addEventText">Aggiungi evento</span>
					</button>
				</div>
			</form>
		</section>
	</div>

	<script>
		const APP_MODE = <?php echo json_encode($appMode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const SHARED_TIMELINE_PAYLOAD = <?php echo json_encode($sharedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	</script>
		<script src="lang/it.js"></script>
		<script src="lang/en.js"></script>
		<script src="app.js"></script>
		<script src="lang/es.js"></script>
		<script src="lang/de.js"></script>
		<script src="lang/fr.js"></script>
		<script src="lang/pt.js"></script>
		<script src="lang/ru.js"></script>
		<script src="lang/tr.js"></script>
		<script src="lang/ja.js"></script>
		<script src="lang/zh.js"></script>
		<!-- PWA: Registrazione service worker -->
		<script>
			if ('serviceWorker' in navigator) {
				window.addEventListener('load', function() {
					navigator.serviceWorker.register('service-worker.js')
						.then(function(registration) {
							// Registrazione riuscita
						}, function(err) {
							// Registrazione fallita
						});
				});
			}
		</script>
</body>
</html>
