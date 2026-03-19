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
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Timeline SalernoHub | Crea Linee Temporali Personalizzate Online</title>
	<meta name="description" content="Crea una linea temporale personalizzata online per scuola, religione, lavoro, studio, storia, progetti ed eventi personali. Timeline semplice, veloce e gratuita.">
	<meta name="keywords" content="linea temporale, timeline online, creare timeline, timeline scuola, timeline religione, timeline lavoro, linea del tempo personalizzata, timeline storia, timeline progetti, timeline eventi">
	<meta name="author" content="SalernoHub">
	<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
	<meta name="googlebot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
	<meta name="bingbot" content="index, follow">
	<link rel="canonical" href="https://timeline.salernohub.net/">
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
	<meta name="theme-color" content="#2563eb">
	<meta name="referrer" content="strict-origin-when-cross-origin">
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
</head>
<body>
	<div class="container">
		<div class="top-content">
			<h1>Linea Temporale</h1>
			<div class="fab-stack top-actions">
				<button type="button" class="fab-add" id="openFormBtn" aria-label="Aggiungi nuovo evento">+</button>
				<div class="backup-wrap">
					<button type="button" class="fab-backup" id="backupMenuBtn" aria-label="Backup" title="Backup">
						<svg class="backup-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M12 3v10"></path>
							<path d="M8 10l4 4 4-4"></path>
							<path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"></path>
						</svg>
					</button>
					<div class="backup-menu hidden" id="backupMenu" role="menu" aria-label="Menu backup">
						<div class="online-save-panel" id="onlineSavePanel">
							<div class="online-save-field">
								<label for="adminLinkInput">Link admin</label>
								<div class="online-save-input-row">
									<input id="adminLinkInput" type="text" readonly placeholder="Link admin (vuoto finché non salvi)">
									<button type="button" class="secondary copy-link-btn" id="copyAdminLinkBtn" aria-label="Copia link admin" title="Copia link admin">
										<svg class="copy-link-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
											<rect x="9" y="9" width="11" height="11" rx="2"></rect>
											<rect x="4" y="4" width="11" height="11" rx="2"></rect>
										</svg>
									</button>
								</div>
							</div>
							<div class="online-save-field">
								<label for="viewerLinkInput">Link solo visualizzatore</label>
								<div class="online-save-input-row">
									<input id="viewerLinkInput" type="text" readonly placeholder="Link pubblico (vuoto finché non salvi)">
									<button type="button" class="secondary copy-link-btn" id="copyViewerLinkBtn" aria-label="Copia link visualizzatore" title="Copia link visualizzatore">
										<svg class="copy-link-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
											<rect x="9" y="9" width="11" height="11" rx="2"></rect>
											<rect x="4" y="4" width="11" height="11" rx="2"></rect>
										</svg>
									</button>
								</div>
							</div>
							<div class="backup-actions-row">
								<button type="button" class="secondary" id="downloadBtn" role="menuitem">Scarica</button>
								<button type="button" class="secondary" id="uploadBtn" role="menuitem">Importa</button>
								<button type="button" class="primary" id="saveOnlineBtn" role="menuitem">Salva</button>
							</div>
						</div>
					</div>
				</div>
				<button type="button" class="fab-fullscreen" id="fullscreenBtn" aria-label="Attiva schermo intero" title="Attiva schermo intero">
					<svg class="fullscreen-icon" id="fullscreenEnterIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"></path>
					</svg>
					<svg class="fullscreen-icon hidden" id="fullscreenExitIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M9 3H3v6M15 3h6v6M21 15v6h-6M3 15v6h6"></path>
					</svg>
				</button>
				<button type="button" class="fab-theme" id="themeToggleBtn" aria-label="Tema scuro" title="Tema scuro">
					<svg class="theme-icon theme-icon-moon" id="themeMoonIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 1 0 9.8 9.8z"></path>
					</svg>
					<svg class="theme-icon theme-icon-sun hidden" id="themeSunIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<circle cx="12" cy="12" r="4"></circle>
						<path d="M12 2v2.5M12 19.5V22M4.93 4.93l1.77 1.77M17.3 17.3l1.77 1.77M2 12h2.5M19.5 12H22M4.93 19.07l1.77-1.77M17.3 6.7l1.77-1.77"></path>
					</svg>
				</button>
				<button type="button" class="muted timeline-reset-btn fab-reset hidden" id="resetTimelineBtn" aria-label="Crea nuova linea temporale locale" title="Nuova linea temporale (solo locale)">
					<svg class="timeline-reset-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M3 6h18"></path>
						<path d="M8 6V4h8v2"></path>
						<path d="M6 6l1 14h10l1-14"></path>
						<path d="M10 10v7M14 10v7"></path>
					</svg>
				</button>
				<input id="uploadInput" class="hidden" type="file" accept="application/json">
			</div>
		</div>

		<section class="card timeline-section">
			<div class="timeline-topbar">
				<div class="timeline-header">
					<button type="button" class="muted timeline-title-edit" id="editTimelineTitleBtn" aria-label="Modifica titolo timeline" title="Modifica titolo">✎</button>
					<h2 id="timelineTitle">Timeline</h2>
				</div>
				<div class="timeline-right-controls">
					<div class="viewer-actions hidden" id="viewerActions" aria-label="Azioni visualizzatore">
						<button type="button" class="primary viewer-action-btn" id="viewerCreateBtn">Crea la tua linea temporale</button>
						<button type="button" class="secondary viewer-action-btn viewer-icon-btn" id="viewerDownloadBtn" aria-label="Scarica timeline" title="Scarica timeline">
							<svg class="viewer-action-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M12 3v10"></path>
								<path d="M8 10l4 4 4-4"></path>
								<path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"></path>
							</svg>
						</button>
						<button type="button" class="secondary viewer-action-btn viewer-icon-btn" id="viewerFullscreenBtn" aria-label="Attiva schermo intero" title="Attiva schermo intero">
							<svg class="viewer-action-icon" id="viewerFullscreenEnterIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"></path>
							</svg>
							<svg class="viewer-action-icon hidden" id="viewerFullscreenExitIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M9 3H3v6M15 3h6v6M21 15v6h-6M3 15v6h6"></path>
							</svg>
						</button>
					</div>
					<div class="timeline-zoom-controls" aria-label="Controlli zoom timeline">
						<button type="button" class="muted timeline-zoom-btn" id="zoomOutBtn" aria-label="Riduci dettagli timeline" title="Riduci dettagli">−</button>
						<button type="button" class="muted timeline-zoom-btn" id="zoomInBtn" aria-label="Aumenta dettagli timeline" title="Aumenta dettagli">+</button>
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
				<button type="button" class="muted close-btn" id="closeLocalResetBtn" aria-label="Chiudi">✕</button>
			</div>
			<p class="local-reset-text">Questa azione cancellerà la timeline solo in locale (dispositivo/browser attuale).</p>
            <p class="local-reset-text">La versione online non verrà eliminata.</p>
			<p class="local-reset-text">Prima di procedere, si consiglia di scaricare la linea temporale o salvarla online.</p>
			<div class="local-reset-actions">
				<button type="button" class="secondary" id="localResetDownloadBtn">Scarica</button>
				<button type="button" class="secondary" id="localResetSaveOnlineBtn">Salva online</button>
				<button type="button" class="danger" id="localResetConfirmBtn">Cancella solo locale</button>
				<button type="button" class="danger" id="localResetDeleteOnlineBtn">Cancella online</button>
			</div>
			<div class="local-reset-online-panel online-save-field hidden" id="localResetAdminLinkWrap">
				<label for="localResetAdminLinkInput">Link admin</label>
				<div class="online-save-input-row">
					<input type="text" id="localResetAdminLinkInput" readonly placeholder="Salva online per ottenere il link admin">
					<button type="button" class="muted copy-link-btn" id="localResetCopyAdminLinkBtn" aria-label="Copia link admin" title="Copia link admin">
						<svg class="copy-link-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<rect x="9" y="9" width="13" height="13" rx="2"></rect>
							<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
						</svg>
					</button>
				</div>
			</div>
		</section>
	</div>

	<div class="mobile-viewer-menu-wrap hidden" id="mobileViewerMenuWrap" aria-label="Azioni visualizzatore mobile">
		<button type="button" class="primary mobile-viewer-action-btn mobile-viewer-create-btn hidden" id="mobileViewerCreateBtn">Crea la tua linea temporale</button>
		<div class="mobile-viewer-menu hidden" id="mobileViewerMenu">
			<button type="button" class="muted mobile-viewer-icon-btn" id="mobileViewerZoomInBtn" aria-label="Aumenta dettagli timeline" title="Aumenta dettagli">+</button>
			<button type="button" class="muted mobile-viewer-icon-btn" id="mobileViewerZoomOutBtn" aria-label="Riduci dettagli timeline" title="Riduci dettagli">−</button>
			<button type="button" class="secondary mobile-viewer-icon-btn" id="mobileViewerThemeBtn" aria-label="Tema scuro" title="Tema scuro">
				<svg class="viewer-action-icon" id="mobileViewerThemeMoonIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 1 0 9.8 9.8z"></path>
				</svg>
				<svg class="viewer-action-icon hidden" id="mobileViewerThemeSunIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<circle cx="12" cy="12" r="4"></circle>
					<path d="M12 2v2.5M12 19.5V22M4.93 4.93l1.77 1.77M17.3 17.3l1.77 1.77M2 12h2.5M19.5 12H22M4.93 19.07l1.77-1.77M17.3 6.7l1.77-1.77"></path>
				</svg>
			</button>
			<button type="button" class="secondary mobile-viewer-icon-btn" id="mobileViewerFullscreenBtn" aria-label="Attiva schermo intero" title="Attiva schermo intero">
				<svg class="viewer-action-icon" id="mobileViewerFullscreenEnterIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"></path>
				</svg>
				<svg class="viewer-action-icon hidden" id="mobileViewerFullscreenExitIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M9 3H3v6M15 3h6v6M21 15v6h-6M3 15v6h6"></path>
				</svg>
			</button>
			<button type="button" class="secondary mobile-viewer-icon-btn" id="mobileViewerDownloadBtn" aria-label="Scarica timeline" title="Scarica timeline">
				<svg class="viewer-action-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M12 3v10"></path>
					<path d="M8 10l4 4 4-4"></path>
					<path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"></path>
				</svg>
			</button>
		</div>
		<button type="button" class="mobile-viewer-menu-btn" id="mobileViewerMenuBtn" aria-label="Apri azioni visualizzatore" title="Azioni visualizzatore">
			<svg class="backup-icon" id="mobileViewerMenuOpenIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M4 7h16M4 12h16M4 17h16"></path>
			</svg>
			<svg class="backup-icon hidden" id="mobileViewerMenuCloseIcon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M6 6l12 12M18 6L6 18"></path>
			</svg>
		</button>
	</div>

	<div class="modal hidden" id="eventModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
		<div class="modal-backdrop" id="modalBackdrop"></div>
		<section class="modal-card">
			<div class="modal-header">
				<h2 id="modalTitle">Nuovo evento</h2>
				<button type="button" class="muted close-btn" id="closeModalBtn" aria-label="Chiudi">✕</button>
			</div>

			<form id="eventForm">
				<input type="hidden" id="editIndex" value="-1">

				<label for="eventDate">Data</label>
				<input id="eventDate" type="date" required>
				<div class="date-visibility-options">
					<label class="checkbox-inline" for="eventShowDay">
						<input id="eventShowDay" type="checkbox" checked>
						Mostra giorno
					</label>
					<label class="checkbox-inline" for="eventShowMonth">
						<input id="eventShowMonth" type="checkbox" checked>
						Mostra mese
					</label>
				</div>
				<div class="date-visibility-options">
					<label class="checkbox-inline" for="eventUseCustomYear">
						<input id="eventUseCustomYear" type="checkbox">
						Usa anno personalizzato
					</label>
					<input id="eventCustomYear" class="year-input hidden" type="number" step="1" placeholder="Es. -500">
				</div>

				<label for="eventEraTag">Targhetta era</label>
				<select id="eventEraTag">
					<option value="none">Nessuna</option>
					<option value="christian">a.C. / d.C.</option>
					<option value="common-era">a.E.V. / E.V.</option>
				</select>

				<label for="eventTitle">Titolo</label>
				<input id="eventTitle" type="text" placeholder="Es. Inizio progetto" required>

				<label for="eventText">Testo</label>
				<textarea id="eventText" placeholder="Descrizione evento" required></textarea>

				<label for="eventImage">Immagine (opzionale)</label>
				<div class="image-picker-row">
					<label id="eventImageTrigger" for="eventImage" class="button-like secondary image-picker-btn">Scegli immagine</label>
					<button type="button" id="removeEventImageBtn" class="muted image-remove-btn hidden">Rimuovi immagine</button>
					<span id="eventImageName" class="image-picker-name">Nessun file selezionato</span>
				</div>
				<input id="eventImage" class="hidden" type="file" accept="image/*">
				<div id="eventImagePreviewWrap" class="image-preview-wrap hidden">
					<img id="eventImagePreview" class="image-preview" src="" alt="Anteprima immagine evento">
				</div>

				<div class="form-actions">
					<button type="submit" class="primary" id="saveEventBtn">Aggiungi evento</button>
				</div>
			</form>
		</section>
	</div>

	<script>
		const APP_MODE = <?php echo json_encode($appMode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const SHARED_TIMELINE_PAYLOAD = <?php echo json_encode($sharedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	</script>
	<script src="app.js"></script>
</body>
</html>
