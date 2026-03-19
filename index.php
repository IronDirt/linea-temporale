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
				<input id="uploadInput" class="hidden" type="file" accept="application/json">
			</div>
		</div>

		<section class="card timeline-section">
			<button type="button" class="muted timeline-reset-btn hidden" id="resetTimelineBtn" aria-label="Crea nuova linea temporale locale" title="Nuova linea temporale (solo locale)">
				<svg class="timeline-reset-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M3 6h18"></path>
					<path d="M8 6V4h8v2"></path>
					<path d="M6 6l1 14h10l1-14"></path>
					<path d="M10 10v7M14 10v7"></path>
				</svg>
			</button>
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
				<button type="button" class="muted" id="localResetCancelBtn">Annulla</button>
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

	<div class="modal hidden" id="localResetModal" role="dialog" aria-modal="true" aria-labelledby="localResetTitle">
		<div class="modal-backdrop" id="localResetBackdrop"></div>
		<section class="modal-card local-reset-card">
			<div class="modal-header">
				<h2 id="localResetTitle">Nuova linea temporale</h2>
				<button type="button" class="muted close-btn" id="closeLocalResetBtn" aria-label="Chiudi">✕</button>
			</div>
			<p class="local-reset-text">Questa azione cancellerà la timeline solo in locale (dispositivo/browser attuale). La versione online non verrà eliminata.</p>
			<p class="local-reset-text">Prima di procedere, si consiglia di scaricare la linea temporale o salvarla online.</p>
			<div class="local-reset-actions">
				<button type="button" class="secondary" id="localResetDownloadBtn">Scarica</button>
				<button type="button" class="secondary" id="localResetSaveOnlineBtn">Salva online</button>
				<button type="button" class="danger" id="localResetConfirmBtn">Cancella solo locale</button>
				<button type="button" class="muted" id="localResetCancelBtn">Annulla</button>
			</div>
		</section>
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
		const STORAGE_KEY = 'timeline_app_data_v1';
		const THEME_KEY = 'timeline_theme_v1';
		const TIMELINE_TITLE_KEY = 'timeline_title_v1';
		const SHARE_LINKS_KEY = 'timeline_share_links_v1';
		const CACHE_NAME = 'timeline_app_cache_v1';
		const CACHE_URL = '/timeline-data.json';
		const IMAGE_MAX_WIDTH = 1600;
		const IMAGE_MAX_HEIGHT = 1600;
		const IMAGE_QUALITY = 0.8;

		const eventForm = document.getElementById('eventForm');
		const editIndexInput = document.getElementById('editIndex');
		const eventDateInput = document.getElementById('eventDate');
		const eventShowDayInput = document.getElementById('eventShowDay');
		const eventShowMonthInput = document.getElementById('eventShowMonth');
		const eventUseCustomYearInput = document.getElementById('eventUseCustomYear');
		const eventCustomYearInput = document.getElementById('eventCustomYear');
		const eventEraTagInput = document.getElementById('eventEraTag');
		const eventTitleInput = document.getElementById('eventTitle');
		const eventTextInput = document.getElementById('eventText');
		const eventImageInput = document.getElementById('eventImage');
		const eventImageTrigger = document.getElementById('eventImageTrigger');
		const removeEventImageBtn = document.getElementById('removeEventImageBtn');
		const eventImageName = document.getElementById('eventImageName');
		const eventImagePreviewWrap = document.getElementById('eventImagePreviewWrap');
		const eventImagePreview = document.getElementById('eventImagePreview');
		const saveEventBtn = document.getElementById('saveEventBtn');
		const fullscreenBtn = document.getElementById('fullscreenBtn');
		const fullscreenEnterIcon = document.getElementById('fullscreenEnterIcon');
		const fullscreenExitIcon = document.getElementById('fullscreenExitIcon');
		const themeToggleBtn = document.getElementById('themeToggleBtn');
		const themeMoonIcon = document.getElementById('themeMoonIcon');
		const themeSunIcon = document.getElementById('themeSunIcon');
		const downloadBtn = document.getElementById('downloadBtn');
		const uploadBtn = document.getElementById('uploadBtn');
		const saveOnlineBtn = document.getElementById('saveOnlineBtn');
		const adminLinkInput = document.getElementById('adminLinkInput');
		const viewerLinkInput = document.getElementById('viewerLinkInput');
		const copyAdminLinkBtn = document.getElementById('copyAdminLinkBtn');
		const copyViewerLinkBtn = document.getElementById('copyViewerLinkBtn');
		const uploadInput = document.getElementById('uploadInput');
		const timelineEl = document.getElementById('timeline');
		const timelineTitleEl = document.getElementById('timelineTitle');
		const editTimelineTitleBtn = document.getElementById('editTimelineTitleBtn');
		const zoomOutBtn = document.getElementById('zoomOutBtn');
		const zoomInBtn = document.getElementById('zoomInBtn');
		const resetTimelineBtn = document.getElementById('resetTimelineBtn');
		const viewerActions = document.getElementById('viewerActions');
		const viewerFullscreenBtn = document.getElementById('viewerFullscreenBtn');
		const viewerFullscreenEnterIcon = document.getElementById('viewerFullscreenEnterIcon');
		const viewerFullscreenExitIcon = document.getElementById('viewerFullscreenExitIcon');
		const viewerDownloadBtn = document.getElementById('viewerDownloadBtn');
		const viewerCreateBtn = document.getElementById('viewerCreateBtn');
		const mobileViewerMenuWrap = document.getElementById('mobileViewerMenuWrap');
		const mobileViewerMenuBtn = document.getElementById('mobileViewerMenuBtn');
		const mobileViewerMenuOpenIcon = document.getElementById('mobileViewerMenuOpenIcon');
		const mobileViewerMenuCloseIcon = document.getElementById('mobileViewerMenuCloseIcon');
		const mobileViewerMenu = document.getElementById('mobileViewerMenu');
		const mobileViewerCreateBtn = document.getElementById('mobileViewerCreateBtn');
		const mobileViewerDownloadBtn = document.getElementById('mobileViewerDownloadBtn');
		const mobileViewerFullscreenBtn = document.getElementById('mobileViewerFullscreenBtn');
		const mobileViewerFullscreenEnterIcon = document.getElementById('mobileViewerFullscreenEnterIcon');
		const mobileViewerFullscreenExitIcon = document.getElementById('mobileViewerFullscreenExitIcon');
		const mobileViewerThemeBtn = document.getElementById('mobileViewerThemeBtn');
		const mobileViewerThemeMoonIcon = document.getElementById('mobileViewerThemeMoonIcon');
		const mobileViewerThemeSunIcon = document.getElementById('mobileViewerThemeSunIcon');
		const mobileViewerZoomOutBtn = document.getElementById('mobileViewerZoomOutBtn');
		const mobileViewerZoomInBtn = document.getElementById('mobileViewerZoomInBtn');
		const openFormBtn = document.getElementById('openFormBtn');
		const backupMenuBtn = document.getElementById('backupMenuBtn');
		const backupMenu = document.getElementById('backupMenu');
		const eventModal = document.getElementById('eventModal');
		const closeModalBtn = document.getElementById('closeModalBtn');
		const modalBackdrop = document.getElementById('modalBackdrop');
		const modalTitle = document.getElementById('modalTitle');
		const localResetModal = document.getElementById('localResetModal');
		const localResetBackdrop = document.getElementById('localResetBackdrop');
		const closeLocalResetBtn = document.getElementById('closeLocalResetBtn');
		const localResetDownloadBtn = document.getElementById('localResetDownloadBtn');
		const localResetSaveOnlineBtn = document.getElementById('localResetSaveOnlineBtn');
		const localResetConfirmBtn = document.getElementById('localResetConfirmBtn');
		const localResetCancelBtn = document.getElementById('localResetCancelBtn');
		const APP_MODE = <?php echo json_encode($appMode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const SHARED_TIMELINE_PAYLOAD = <?php echo json_encode($sharedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
		const START_WITH_EMPTY_TIMELINE = new URLSearchParams(window.location.search).get('new') === '1';

		let timelineData = [];
		let currentTheme = 'light';
		let zoomLevel = 0;
		let imagePreviewObjectUrl = '';
		let removeImageOnSave = false;
		let sharedTimelineId = null;
		let sharedAdminToken = null;

		function hideImagePreview() {
			if (imagePreviewObjectUrl) {
				URL.revokeObjectURL(imagePreviewObjectUrl);
				imagePreviewObjectUrl = '';
			}

			eventImagePreview.removeAttribute('src');
			eventImagePreviewWrap.classList.add('hidden');
		}

		function showImagePreview(src) {
			eventImagePreview.src = src;
			eventImagePreviewWrap.classList.remove('hidden');
		}

		function showImagePreviewFromFile(file) {
			if (!file) {
				hideImagePreview();
				return;
			}

			if (imagePreviewObjectUrl) {
				URL.revokeObjectURL(imagePreviewObjectUrl);
			}

			imagePreviewObjectUrl = URL.createObjectURL(file);
			showImagePreview(imagePreviewObjectUrl);
		}

		function getTimelineAnchor() {
			const items = timelineEl.querySelectorAll('.timeline-item');
			if (!items.length) {
				return null;
			}

			const viewportCenterX = timelineEl.scrollLeft + (timelineEl.clientWidth / 2);
			let nearestItem = items[0];
			let nearestDistance = Number.POSITIVE_INFINITY;

			items.forEach((item) => {
				const itemCenterX = item.offsetLeft + (item.offsetWidth / 2);
				const distance = Math.abs(itemCenterX - viewportCenterX);
				if (distance < nearestDistance) {
					nearestDistance = distance;
					nearestItem = item;
				}
			});

			const itemWidth = Math.max(1, nearestItem.offsetWidth);
			const relativeCenter = (viewportCenterX - nearestItem.offsetLeft) / itemWidth;

			return {
				eventId: nearestItem.dataset.eventId || '',
				relativeCenter
			};
		}

		function restoreTimelineAnchor(anchor, smooth = true) {
			if (!anchor || !anchor.eventId) {
				return;
			}

			window.requestAnimationFrame(() => {
				const anchorItem = Array.from(timelineEl.querySelectorAll('.timeline-item'))
					.find((item) => item.dataset.eventId === anchor.eventId);

				if (!anchorItem) {
					return;
				}

				const itemWidth = Math.max(1, anchorItem.offsetWidth);
				const targetCenterX = anchorItem.offsetLeft + (itemWidth * anchor.relativeCenter);
				const maxScrollLeft = Math.max(0, timelineEl.scrollWidth - timelineEl.clientWidth);
				const targetScrollLeft = Math.min(
					maxScrollLeft,
					Math.max(0, targetCenterX - (timelineEl.clientWidth / 2))
				);

				timelineEl.scrollTo({
					left: targetScrollLeft,
					behavior: smooth ? 'smooth' : 'auto'
				});
			});
		}

		function applyZoomToTimeline(options = {}) {
			const { preserveFocus = false, smooth = true } = options;
			const items = timelineEl.querySelectorAll('.timeline-item');

			if (!items.length) {
				updateZoomButtons();
				updateTimelineLineWidth();
				return;
			}

			const anchor = preserveFocus ? getTimelineAnchor() : null;
			const visibilityStep = zoomLevel + 1;
			const lastIndex = items.length - 1;

			items.forEach((item, sortedIndex) => {
				const isEdgeItem = sortedIndex === 0 || sortedIndex === lastIndex;
				const isPinned = item.dataset.pinned === 'true';
				const isCollapsed = zoomLevel > 0 && !isEdgeItem && !isPinned && (sortedIndex % visibilityStep) !== 0;
				item.classList.toggle('timeline-item-collapsed', isCollapsed);
			});

			updateZoomDensity(items.length);

			updateZoomButtons();
			updateTimelineLineWidth();

			if (anchor) {
				restoreTimelineAnchor(anchor, smooth);
			}
		}

		function updateZoomButtons() {
			const hasItems = timelineData.length > 0;
			const maxZoomLevel = hasItems ? Math.max(0, timelineData.length - 1) : 0;
			zoomInBtn.disabled = zoomLevel <= 0;
			zoomOutBtn.disabled = !hasItems || zoomLevel >= maxZoomLevel;
		}

		function updateZoomDensity(itemsCount) {
			if (!itemsCount || zoomLevel <= 0) {
				timelineEl.style.removeProperty('--timeline-collapsed-item-width');
				timelineEl.style.removeProperty('--timeline-gap');
				timelineEl.style.removeProperty('--timeline-dot-size');
				return;
			}

			const itemDensity = Math.max(0, itemsCount - 10) / 30;
			const zoomDensity = zoomLevel / Math.max(1, itemsCount - 1);
			const compactness = Math.min(1, (itemDensity * 0.75) + (zoomDensity * 2.2));

			const collapsedWidth = Math.round(54 - (compactness * 28));
			const gap = Math.round(14 - (compactness * 8));
			const dotSize = Math.round(10 - (compactness * 2));

			timelineEl.style.setProperty('--timeline-collapsed-item-width', `${Math.max(24, collapsedWidth)}px`);
			timelineEl.style.setProperty('--timeline-gap', `${Math.max(5, gap)}px`);
			timelineEl.style.setProperty('--timeline-dot-size', `${Math.max(7, dotSize)}px`);
		}

		function applyTheme(theme) {
			currentTheme = theme === 'dark' ? 'dark' : 'light';
			document.body.classList.toggle('theme-dark', currentTheme === 'dark');
			const isDark = currentTheme === 'dark';
			themeMoonIcon.classList.toggle('hidden', isDark);
			themeSunIcon.classList.toggle('hidden', !isDark);
			themeToggleBtn.title = isDark ? 'Tema chiaro' : 'Tema scuro';
			themeToggleBtn.setAttribute('aria-label', isDark ? 'Passa al tema chiaro' : 'Passa al tema scuro');
			if (mobileViewerThemeBtn) {
				mobileViewerThemeMoonIcon.classList.toggle('hidden', isDark);
				mobileViewerThemeSunIcon.classList.toggle('hidden', !isDark);
				mobileViewerThemeBtn.title = isDark ? 'Tema chiaro' : 'Tema scuro';
				mobileViewerThemeBtn.setAttribute('aria-label', isDark ? 'Passa al tema chiaro' : 'Passa al tema scuro');
			}
		}

		function loadTheme() {
			const savedTheme = localStorage.getItem(THEME_KEY);
			if (savedTheme === 'light' || savedTheme === 'dark') {
				return savedTheme;
			}

			const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			return prefersDark ? 'dark' : 'light';
		}

		function showStatus() {
			return;
		}

		function isViewerMode() {
			return APP_MODE === 'viewer';
		}

		function applyAppMode() {
			const viewerMode = isViewerMode();
			if (!viewerMode) {
				return;
			}

			openFormBtn.classList.add('hidden');
			backupMenuBtn.classList.add('hidden');
			editTimelineTitleBtn.classList.add('hidden');
			fullscreenBtn.classList.add('hidden');
			themeToggleBtn.classList.add('hidden');
			resetTimelineBtn.classList.add('hidden');
			viewerActions.classList.remove('hidden');
			mobileViewerMenuWrap.classList.remove('hidden');
			document.body.classList.add('viewer-mode');
			document.body.classList.add('presentation-mode');
		}

		function updateResetTimelineButton() {
			const shouldShow = !isViewerMode() && timelineData.length > 0;
			resetTimelineBtn.classList.toggle('hidden', !shouldShow);
		}

		function toggleMobileViewerMenu() {
			const willOpen = mobileViewerMenu.classList.contains('hidden');
			mobileViewerMenu.classList.toggle('hidden');
			mobileViewerCreateBtn.classList.toggle('hidden');
			mobileViewerMenuBtn.classList.toggle('is-open', willOpen);
			mobileViewerMenuOpenIcon.classList.toggle('hidden', willOpen);
			mobileViewerMenuCloseIcon.classList.toggle('hidden', !willOpen);
			mobileViewerMenuBtn.setAttribute('aria-label', willOpen ? 'Chiudi azioni visualizzatore' : 'Apri azioni visualizzatore');
			mobileViewerMenuBtn.title = willOpen ? 'Chiudi azioni visualizzatore' : 'Azioni visualizzatore';
		}

		function closeMobileViewerMenu() {
			mobileViewerMenu.classList.add('hidden');
			mobileViewerCreateBtn.classList.add('hidden');
			mobileViewerMenuBtn.classList.remove('is-open');
			mobileViewerMenuOpenIcon.classList.remove('hidden');
			mobileViewerMenuCloseIcon.classList.add('hidden');
			mobileViewerMenuBtn.setAttribute('aria-label', 'Apri azioni visualizzatore');
			mobileViewerMenuBtn.title = 'Azioni visualizzatore';
		}

		function goToNewEmptyTimeline() {
			window.location.href = `${window.location.pathname}?new=1`;
		}

		function loadTimelineTitle() {
			const savedTitle = localStorage.getItem(TIMELINE_TITLE_KEY);
			if (!savedTitle || !savedTitle.trim()) {
				return 'Timeline';
			}

			return savedTitle.trim();
		}

		function setTimelineTitle(title) {
			const normalizedTitle = title && title.trim() ? title.trim() : 'Timeline';
			timelineTitleEl.textContent = normalizedTitle;
			localStorage.setItem(TIMELINE_TITLE_KEY, normalizedTitle);
		}

		function updateFullscreenState() {
			const isFullscreen = Boolean(document.fullscreenElement);
			document.body.classList.toggle('presentation-mode', isFullscreen || isViewerMode());
			fullscreenEnterIcon.classList.toggle('hidden', isFullscreen);
			fullscreenExitIcon.classList.toggle('hidden', !isFullscreen);
			fullscreenBtn.title = isFullscreen ? 'Esci da schermo intero' : 'Attiva schermo intero';
			fullscreenBtn.setAttribute('aria-label', isFullscreen ? 'Esci da schermo intero' : 'Attiva schermo intero');
			if (viewerFullscreenBtn) {
				viewerFullscreenBtn.title = isFullscreen ? 'Esci da schermo intero' : 'Attiva schermo intero';
				viewerFullscreenBtn.setAttribute('aria-label', isFullscreen ? 'Esci da schermo intero' : 'Attiva schermo intero');
				viewerFullscreenEnterIcon.classList.toggle('hidden', isFullscreen);
				viewerFullscreenExitIcon.classList.toggle('hidden', !isFullscreen);
			}
			if (mobileViewerFullscreenBtn) {
				mobileViewerFullscreenBtn.title = isFullscreen ? 'Esci da schermo intero' : 'Attiva schermo intero';
				mobileViewerFullscreenBtn.setAttribute('aria-label', isFullscreen ? 'Esci da schermo intero' : 'Attiva schermo intero');
				mobileViewerFullscreenEnterIcon.classList.toggle('hidden', isFullscreen);
				mobileViewerFullscreenExitIcon.classList.toggle('hidden', !isFullscreen);
			}
		}

		async function toggleFullscreenMode(logLabel = 'Errore modalità schermo intero:') {
			try {
				if (!document.fullscreenElement) {
					await document.documentElement.requestFullscreen();
				} else {
					await document.exitFullscreen();
				}
			} catch (error) {
				console.error(logLabel, error);
			}
		}

		function handleZoomOut() {
			if (!timelineData.length) {
				return;
			}
			zoomLevel = Math.min(zoomLevel + 1, timelineData.length - 1);
			applyZoomToTimeline({ preserveFocus: true, smooth: true });
		}

		function handleZoomIn() {
			zoomLevel = Math.max(zoomLevel - 1, 0);
			applyZoomToTimeline({ preserveFocus: true, smooth: true });
		}

		function updateTimelineLineWidth() {
			window.requestAnimationFrame(() => {
				const lineWidth = Math.max(timelineEl.scrollWidth, timelineEl.clientWidth);
				timelineEl.style.setProperty('--timeline-line-width', `${lineWidth}px`);
			});
		}

		function updateDateModeUI() {
			const useCustomYear = eventUseCustomYearInput.checked;
			eventDateInput.disabled = useCustomYear;
			eventDateInput.required = !useCustomYear;
			eventCustomYearInput.classList.toggle('hidden', !useCustomYear);
			eventCustomYearInput.disabled = !useCustomYear;
		}

		function formatYearWithEra(year, eraTag) {
			if (eraTag === 'christian') {
				return `${Math.abs(year)} ${year < 0 ? 'a.C.' : 'd.C.'}`;
			}

			if (eraTag === 'common-era') {
				return `${Math.abs(year)} ${year < 0 ? 'a.E.V.' : 'E.V.'}`;
			}

			return String(year);
		}

		function formatDate(isoDate, options = {}) {
			const { showDay = true, showMonth = true, eraTag = 'none' } = options;
			if (!isoDate) {
				return '';
			}
			const date = new Date(isoDate + 'T00:00:00');
			if (Number.isNaN(date.getTime())) {
				return isoDate;
			}

			const day = String(date.getDate());
			const month = date.toLocaleDateString('it-IT', { month: 'long' });
			const year = formatYearWithEra(date.getFullYear(), eraTag);

			if (showDay && showMonth) {
				return `${day} ${month} ${year}`;
			}

			if (showMonth) {
				return `${month} ${year}`;
			}

			if (showDay) {
				return `${day} ${year}`;
			}

			return year;
		}

		function getEventSortParts(eventItem) {
			if (eventItem.useCustomYear && Number.isInteger(eventItem.customYear)) {
				return {
					year: eventItem.customYear,
					month: 0,
					day: 0
				};
			}

			if (typeof eventItem.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(eventItem.date)) {
				const [year, month, day] = eventItem.date.split('-').map((value) => Number.parseInt(value, 10));
				return {
					year,
					month,
					day
				};
			}

			return {
				year: Number.MAX_SAFE_INTEGER,
				month: Number.MAX_SAFE_INTEGER,
				day: Number.MAX_SAFE_INTEGER
			};
		}

		function compareEventsByDate(leftEvent, rightEvent) {
			const left = getEventSortParts(leftEvent);
			const right = getEventSortParts(rightEvent);

			if (left.year !== right.year) {
				return left.year - right.year;
			}

			if (left.month !== right.month) {
				return left.month - right.month;
			}

			if (left.day !== right.day) {
				return left.day - right.day;
			}

			return 0;
		}

		function renderTimeline() {
			updateResetTimelineButton();

			if (!timelineData.length) {
				timelineEl.innerHTML = '<p class="empty">Nessun evento inserito.</p>';
				updateZoomButtons();
				updateTimelineLineWidth();
				return;
			}

			const sorted = [...timelineData].sort(compareEventsByDate);
			const maxZoomLevel = Math.max(0, sorted.length - 1);
			zoomLevel = Math.min(zoomLevel, maxZoomLevel);
			timelineEl.innerHTML = '';

			sorted.forEach((eventItem) => {
				const originalIndex = timelineData.findIndex((item) => item.id === eventItem.id);
				const isPinned = Boolean(eventItem.pinned);

				const item = document.createElement('article');
				item.className = 'timeline-item';
				item.dataset.eventId = eventItem.id;
				item.dataset.pinned = String(isPinned);

				const imageBlock = eventItem.imageData
					? `<img class="timeline-image" src="${eventItem.imageData}" alt="${escapeHtml(eventItem.title)}" loading="lazy" decoding="async">`
					: '';

				const hasCustomYear = eventItem.useCustomYear && Number.isInteger(eventItem.customYear);
				const formattedDate = hasCustomYear
					? formatYearWithEra(eventItem.customYear, eventItem.eraTag || 'none')
					: formatDate(eventItem.date, {
						showDay: eventItem.showDay !== false,
						showMonth: eventItem.showMonth !== false,
						eraTag: eventItem.eraTag || 'none'
					});

				item.innerHTML = `
					<button type="button" class="timeline-pin-btn${isPinned ? ' is-pinned' : ''}" data-action="pin" data-index="${originalIndex}" aria-label="${isPinned ? 'Rimuovi appuntatura evento' : 'Appunta evento'}" title="${isPinned ? 'Rimuovi appuntatura' : 'Appunta evento'}"><img class="timeline-pin-icon" src="pin-icon.png" alt="" loading="lazy" decoding="async"></button>
					<div class="timeline-item-content">
						<div class="timeline-date">${formattedDate}</div>
						${imageBlock}
						<h3 class="timeline-title">${escapeHtml(eventItem.title)}</h3>
						<p class="timeline-text">${escapeHtml(eventItem.text)}</p>
						<div class="item-actions">
							<button type="button" class="secondary" data-action="edit" data-index="${originalIndex}">Modifica</button>
							<button type="button" class="danger" data-action="delete" data-index="${originalIndex}">Elimina</button>
						</div>
					</div>
				`;

				timelineEl.appendChild(item);
			});

			applyZoomToTimeline({ preserveFocus: false, smooth: false });
			updateResetTimelineButton();
		}

		function escapeHtml(text) {
			return text
				.replaceAll('&', '&amp;')
				.replaceAll('<', '&lt;')
				.replaceAll('>', '&gt;')
				.replaceAll('"', '&quot;')
				.replaceAll("'", '&#39;')
				.replaceAll('\n', '<br>');
		}

		function normalizeImportedEvents(importedEvents) {
			if (!Array.isArray(importedEvents)) {
				return [];
			}

			return importedEvents.map((item) => ({
				id: typeof item.id === 'string' ? item.id : crypto.randomUUID(),
				date: typeof item.date === 'string' ? item.date : '',
				useCustomYear: Boolean(item.useCustomYear),
				customYear: Number.isInteger(item.customYear)
					? item.customYear
					: (typeof item.customYear === 'number' ? Math.trunc(item.customYear) : null),
				eraTag: ['none', 'christian', 'common-era'].includes(item.eraTag)
					? item.eraTag
					: 'none',
				showDay: item.showDay !== false,
				showMonth: item.showMonth !== false,
				title: typeof item.title === 'string' ? item.title : '',
				text: typeof item.text === 'string' ? item.text : '',
				imageData: typeof item.imageData === 'string' ? item.imageData : null,
				pinned: Boolean(item.pinned)
			})).filter((item) => (item.date || (item.useCustomYear && Number.isInteger(item.customYear))) && item.title && item.text);
		}

		function persistOnlineShareState() {
			const payload = {
				timelineId: sharedTimelineId,
				adminToken: sharedAdminToken,
				adminUrl: adminLinkInput.value.trim(),
				viewerUrl: viewerLinkInput.value.trim()
			};

			localStorage.setItem(SHARE_LINKS_KEY, JSON.stringify(payload));
		}

		function restoreOnlineShareStateFromLocal() {
			const raw = localStorage.getItem(SHARE_LINKS_KEY);
			if (!raw) {
				return;
			}

			try {
				const parsed = JSON.parse(raw);
				if (!parsed || typeof parsed !== 'object') {
					return;
				}

				sharedTimelineId = typeof parsed.timelineId === 'string' && parsed.timelineId
					? parsed.timelineId
					: sharedTimelineId;
				sharedAdminToken = typeof parsed.adminToken === 'string' && parsed.adminToken
					? parsed.adminToken
					: sharedAdminToken;

				adminLinkInput.value = typeof parsed.adminUrl === 'string' ? parsed.adminUrl : '';
				viewerLinkInput.value = typeof parsed.viewerUrl === 'string' ? parsed.viewerUrl : '';
			} catch (error) {
				console.error('Errore parsing share links localStorage:', error);
			}
		}

		async function saveToLocal() {
			localStorage.setItem(STORAGE_KEY, JSON.stringify(timelineData));

			if ('caches' in window) {
				try {
					const cache = await caches.open(CACHE_NAME);
					const payload = JSON.stringify({
						savedAt: new Date().toISOString(),
						events: timelineData
					});

					const response = new Response(payload, {
						headers: { 'Content-Type': 'application/json' }
					});

					await cache.put(CACHE_URL, response);
				} catch (error) {
					console.error('Errore Cache API:', error);
				}
			}
		}

		async function loadFromLocal() {
			if (START_WITH_EMPTY_TIMELINE) {
				localStorage.removeItem(STORAGE_KEY);
				localStorage.removeItem(SHARE_LINKS_KEY);
				localStorage.removeItem(TIMELINE_TITLE_KEY);

				if ('caches' in window) {
					try {
						const cache = await caches.open(CACHE_NAME);
						await cache.delete(CACHE_URL);
					} catch (error) {
						console.error('Errore reset cache timeline:', error);
					}
				}

				timelineData = [];
				sharedTimelineId = null;
				sharedAdminToken = null;
				adminLinkInput.value = '';
				viewerLinkInput.value = '';
				setTimelineTitle('Timeline');
				window.history.replaceState({}, '', window.location.pathname);
				return;
			}

			if (SHARED_TIMELINE_PAYLOAD && typeof SHARED_TIMELINE_PAYLOAD === 'object') {
				sharedTimelineId = typeof SHARED_TIMELINE_PAYLOAD.timelineId === 'string' ? SHARED_TIMELINE_PAYLOAD.timelineId : null;
				sharedAdminToken = typeof SHARED_TIMELINE_PAYLOAD.adminToken === 'string' ? SHARED_TIMELINE_PAYLOAD.adminToken : null;
				timelineData = normalizeImportedEvents(SHARED_TIMELINE_PAYLOAD.events || []);

				if (typeof SHARED_TIMELINE_PAYLOAD.title === 'string' && SHARED_TIMELINE_PAYLOAD.title.trim()) {
					setTimelineTitle(SHARED_TIMELINE_PAYLOAD.title.trim());
				}

				adminLinkInput.value = typeof SHARED_TIMELINE_PAYLOAD.adminUrl === 'string'
					? SHARED_TIMELINE_PAYLOAD.adminUrl
					: '';
				viewerLinkInput.value = typeof SHARED_TIMELINE_PAYLOAD.viewerUrl === 'string'
					? SHARED_TIMELINE_PAYLOAD.viewerUrl
					: '';
				if (!isViewerMode()) {
					persistOnlineShareState();
				}
				return;
			}

			restoreOnlineShareStateFromLocal();

			const raw = localStorage.getItem(STORAGE_KEY);
			if (raw) {
				try {
					const parsed = JSON.parse(raw);
					if (Array.isArray(parsed)) {
						timelineData = parsed;
						return;
					}
				} catch (error) {
					console.error('Errore parsing localStorage:', error);
				}
			}

			if ('caches' in window) {
				try {
					const cache = await caches.open(CACHE_NAME);
					const response = await cache.match(CACHE_URL);
					if (response) {
						const cached = await response.json();
						if (cached && Array.isArray(cached.events)) {
							timelineData = cached.events;
						}
					}
				} catch (error) {
					console.error('Errore lettura cache:', error);
				}
			}
		}

		async function saveOnlineTimeline() {
			saveOnlineBtn.disabled = true;
			const originalLabel = saveOnlineBtn.textContent;
			saveOnlineBtn.textContent = 'Salvataggio...';

			try {
				const response = await fetch('?api=save', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						timelineId: sharedTimelineId,
						adminToken: sharedAdminToken,
						title: timelineTitleEl.textContent.trim() || 'Timeline',
						events: timelineData
					})
				});

				const result = await response.json();

				if (!response.ok || !result.ok) {
					throw new Error(result && result.message ? result.message : 'Salvataggio online non riuscito.');
				}

				sharedTimelineId = typeof result.timelineId === 'string' ? result.timelineId : sharedTimelineId;
				sharedAdminToken = typeof result.adminToken === 'string' ? result.adminToken : sharedAdminToken;
				adminLinkInput.value = typeof result.adminUrl === 'string' ? result.adminUrl : '';
				viewerLinkInput.value = typeof result.viewerUrl === 'string' ? result.viewerUrl : '';
				persistOnlineShareState();
				showStatus('Timeline salvata online.');
			} catch (error) {
				window.alert(error instanceof Error ? error.message : 'Errore durante il salvataggio online.');
			} finally {
				saveOnlineBtn.disabled = false;
				saveOnlineBtn.textContent = originalLabel;
			}
		}

		async function clearLocalTimelineData() {
			const confirmed = window.confirm(
				'Attenzione: questa azione cancellerà la linea temporale solo in locale (su questo dispositivo/browser).\n\n'
				+ 'La versione online non verrà eliminata.\n'
				+ 'Si consiglia di scaricare la linea o salvarla online prima di continuare.\n\n'
				+ 'Vuoi continuare?'
			);

			if (!confirmed) {
				return;
			}

			timelineData = [];
			zoomLevel = 0;
			sharedTimelineId = null;
			sharedAdminToken = null;
			adminLinkInput.value = '';
			viewerLinkInput.value = '';

			localStorage.removeItem(STORAGE_KEY);
			localStorage.removeItem(SHARE_LINKS_KEY);
			localStorage.removeItem(TIMELINE_TITLE_KEY);

			if ('caches' in window) {
				try {
					const cache = await caches.open(CACHE_NAME);
					await cache.delete(CACHE_URL);
				} catch (error) {
					console.error('Errore reset cache timeline:', error);
				}
			}

			setTimelineTitle('Timeline');
			resetForm();
			closeModal();
			closeLocalResetModal();
			closeBackupMenu();
			renderTimeline();
		}

		async function copyToClipboard(text) {
			if (!text) {
				return false;
			}

			if (navigator.clipboard && window.isSecureContext) {
				await navigator.clipboard.writeText(text);
				return true;
			}

			const temporaryInput = document.createElement('textarea');
			temporaryInput.value = text;
			temporaryInput.setAttribute('readonly', 'true');
			temporaryInput.style.position = 'fixed';
			temporaryInput.style.top = '-9999px';
			document.body.appendChild(temporaryInput);
			temporaryInput.select();

			let copied = false;
			try {
				copied = document.execCommand('copy');
			} finally {
				temporaryInput.remove();
			}

			return copied;
		}

		async function handleCopyLink(inputElement, buttonElement) {
			const value = inputElement.value.trim();
			if (!value) {
				return;
			}

			try {
				const copied = await copyToClipboard(value);
				if (!copied) {
					throw new Error('Copia non riuscita');
				}
				buttonElement.classList.add('is-copied');
				window.setTimeout(() => {
					buttonElement.classList.remove('is-copied');
				}, 1200);
			} catch (error) {
				window.alert('Impossibile copiare il link automaticamente.');
			}
		}

		function resetForm() {
			editIndexInput.value = '-1';
			eventDateInput.value = '';
			eventShowDayInput.checked = true;
			eventShowMonthInput.checked = true;
			eventUseCustomYearInput.checked = false;
			eventCustomYearInput.value = '';
			eventEraTagInput.value = 'none';
			updateDateModeUI();
			eventTitleInput.value = '';
			eventTextInput.value = '';
			eventImageInput.value = '';
			eventImageName.textContent = 'Nessun file selezionato';
			removeImageOnSave = false;
			removeEventImageBtn.classList.add('hidden');
			hideImagePreview();
			saveEventBtn.textContent = 'Aggiungi evento';
			modalTitle.textContent = 'Nuovo evento';
		}

		function openModal() {
			eventModal.classList.remove('hidden');
			document.body.classList.add('modal-open');
			eventDateInput.focus();
		}

		function closeModal() {
			eventModal.classList.add('hidden');
			document.body.classList.remove('modal-open');
		}

		function openLocalResetModal() {
			localResetModal.classList.remove('hidden');
			document.body.classList.add('modal-open');
		}

		function closeLocalResetModal() {
			localResetModal.classList.add('hidden');
			document.body.classList.remove('modal-open');
		}

		function toggleBackupMenu() {
			backupMenu.classList.toggle('hidden');
		}

		function closeBackupMenu() {
			backupMenu.classList.add('hidden');
		}

		function fileToDataUrl(file) {
			return new Promise((resolve, reject) => {
				const reader = new FileReader();
				reader.onload = () => resolve(reader.result);
				reader.onerror = () => reject(new Error('Impossibile leggere il file immagine.'));
				reader.readAsDataURL(file);
			});
		}

		async function fileToCompressedDataUrl(file) {
			if (!file.type.startsWith('image/')) {
				throw new Error('Il file selezionato non è un\'immagine valida.');
			}

			const sourceDataUrl = await fileToDataUrl(file);

			return new Promise((resolve) => {
				const image = new Image();

				image.onload = () => {
					const scale = Math.min(
						1,
						IMAGE_MAX_WIDTH / image.naturalWidth,
						IMAGE_MAX_HEIGHT / image.naturalHeight
					);

					const targetWidth = Math.max(1, Math.round(image.naturalWidth * scale));
					const targetHeight = Math.max(1, Math.round(image.naturalHeight * scale));

					const canvas = document.createElement('canvas');
					canvas.width = targetWidth;
					canvas.height = targetHeight;

					const context = canvas.getContext('2d');
					if (!context) {
						resolve(sourceDataUrl);
						return;
					}

					context.drawImage(image, 0, 0, targetWidth, targetHeight);

					const compressedDataUrl = canvas.toDataURL('image/webp', IMAGE_QUALITY);
					resolve(compressedDataUrl.length < sourceDataUrl.length ? compressedDataUrl : sourceDataUrl);
				};

				image.onerror = () => resolve(sourceDataUrl);
				image.src = sourceDataUrl;
			});
		}

		function downloadTimelineData() {
			const payload = {
				exportedAt: new Date().toISOString(),
				version: 1,
				events: timelineData
			};

			const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
			const url = URL.createObjectURL(blob);

			const a = document.createElement('a');
			a.href = url;
			a.download = 'timeline-data.json';
			document.body.appendChild(a);
			a.click();
			a.remove();

			URL.revokeObjectURL(url);
			showStatus('File JSON scaricato.');
		}

		eventForm.addEventListener('submit', async (event) => {
			event.preventDefault();

			const date = eventDateInput.value;
			const showDay = eventShowDayInput.checked;
			const showMonth = eventShowMonthInput.checked;
			const useCustomYear = eventUseCustomYearInput.checked;
			const eraTag = eventEraTagInput.value;
			const title = eventTitleInput.value.trim();
			const text = eventTextInput.value.trim();
			const customYear = useCustomYear
				? Number.parseInt(eventCustomYearInput.value, 10)
				: null;

			const hasInvalidCustomYear = useCustomYear && !Number.isInteger(customYear);
			if ((!useCustomYear && !date) || hasInvalidCustomYear || !title || !text) {
				showStatus('Compila data, titolo e testo.', true);
				return;
			}

			const editIndex = Number.parseInt(editIndexInput.value, 10);
			let imageData = null;

			if (eventImageInput.files[0]) {
				try {
					imageData = await fileToCompressedDataUrl(eventImageInput.files[0]);
				} catch (error) {
					showStatus(error.message, true);
					return;
				}
			}

			if (editIndex >= 0 && timelineData[editIndex]) {
				const previous = timelineData[editIndex];
				const nextImageData = removeImageOnSave
					? null
					: (imageData ?? previous.imageData);
				timelineData[editIndex] = {
					...previous,
					date: useCustomYear ? '' : date,
					useCustomYear,
					customYear,
					eraTag,
					showDay,
					showMonth,
					title,
					text,
					imageData: nextImageData,
					pinned: Boolean(previous.pinned)
				};
				showStatus('Evento aggiornato con successo.');
			} else {
				timelineData.push({
					id: crypto.randomUUID(),
					date: useCustomYear ? '' : date,
					useCustomYear,
					customYear,
					eraTag,
					showDay,
					showMonth,
					title,
					text,
					imageData,
					pinned: false
				});
				showStatus('Evento aggiunto con successo.');
			}

			await saveToLocal();
			renderTimeline();
			resetForm();
			closeModal();
		});

		openFormBtn.addEventListener('click', () => {
			resetForm();
			openModal();
			closeBackupMenu();
			showStatus('Inserisci i dati del nuovo evento.');
		});

		backupMenuBtn.addEventListener('click', (event) => {
			event.stopPropagation();
			toggleBackupMenu();
		});

		uploadBtn.addEventListener('click', () => {
			closeBackupMenu();
			uploadInput.click();
		});

		saveOnlineBtn.addEventListener('click', () => {
			saveOnlineTimeline();
		});

		copyAdminLinkBtn.addEventListener('click', () => {
			handleCopyLink(adminLinkInput, copyAdminLinkBtn);
		});

		copyViewerLinkBtn.addEventListener('click', () => {
			handleCopyLink(viewerLinkInput, copyViewerLinkBtn);
		});

		themeToggleBtn.addEventListener('click', () => {
			const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
			applyTheme(nextTheme);
			localStorage.setItem(THEME_KEY, nextTheme);
		});

		zoomOutBtn.addEventListener('click', handleZoomOut);

		zoomInBtn.addEventListener('click', handleZoomIn);

		resetTimelineBtn.addEventListener('click', () => {
			openLocalResetModal();
		});

		closeLocalResetBtn.addEventListener('click', closeLocalResetModal);
		localResetCancelBtn.addEventListener('click', closeLocalResetModal);
		localResetBackdrop.addEventListener('click', closeLocalResetModal);

		localResetDownloadBtn.addEventListener('click', () => {
			downloadTimelineData();
		});

		localResetSaveOnlineBtn.addEventListener('click', () => {
			saveOnlineTimeline();
		});

		localResetConfirmBtn.addEventListener('click', () => {
			clearLocalTimelineData();
		});

		editTimelineTitleBtn.addEventListener('click', () => {
			const currentTitle = timelineTitleEl.textContent.trim() || 'Timeline';
			const nextTitle = window.prompt('Inserisci il titolo della timeline', currentTitle);

			if (nextTitle === null) {
				return;
			}

			setTimelineTitle(nextTitle);
		});

		fullscreenBtn.addEventListener('click', async () => {
			closeBackupMenu();
			await toggleFullscreenMode('Errore modalità schermo intero:');
		});

		viewerFullscreenBtn.addEventListener('click', async () => {
			await toggleFullscreenMode('Errore modalità schermo intero (viewer):');
		});

		viewerDownloadBtn.addEventListener('click', () => {
			downloadTimelineData();
		});

		viewerCreateBtn.addEventListener('click', () => {
			goToNewEmptyTimeline();
		});

		mobileViewerMenuBtn.addEventListener('click', (event) => {
			event.stopPropagation();
			toggleMobileViewerMenu();
		});

		mobileViewerFullscreenBtn.addEventListener('click', async () => {
			await toggleFullscreenMode('Errore modalità schermo intero (viewer mobile):');
		});

		mobileViewerDownloadBtn.addEventListener('click', () => {
			downloadTimelineData();
		});

		mobileViewerCreateBtn.addEventListener('click', () => {
			goToNewEmptyTimeline();
		});

		mobileViewerThemeBtn.addEventListener('click', () => {
			const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
			applyTheme(nextTheme);
			localStorage.setItem(THEME_KEY, nextTheme);
		});

		mobileViewerZoomOutBtn.addEventListener('click', handleZoomOut);

		mobileViewerZoomInBtn.addEventListener('click', handleZoomIn);

		document.addEventListener('fullscreenchange', updateFullscreenState);
		window.addEventListener('resize', updateTimelineLineWidth);

		closeModalBtn.addEventListener('click', closeModal);
		modalBackdrop.addEventListener('click', closeModal);

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && !eventModal.classList.contains('hidden')) {
				closeModal();
			}

			if (event.key === 'Escape' && !localResetModal.classList.contains('hidden')) {
				closeLocalResetModal();
			}

			if (event.key === 'Escape') {
				closeBackupMenu();
				closeMobileViewerMenu();
			}
		});

		document.addEventListener('click', (event) => {
			if (!(event.target instanceof HTMLElement)) {
				return;
			}

			if (!backupMenu.contains(event.target) && event.target !== backupMenuBtn) {
				closeBackupMenu();
			}

			if (!mobileViewerMenuWrap.contains(event.target)) {
				closeMobileViewerMenu();
			}
		});

		timelineEl.addEventListener('click', (event) => {
			if (isViewerMode()) {
				return;
			}

			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}

			const actionElement = target.closest('[data-action]');
			if (!(actionElement instanceof HTMLElement)) {
				return;
			}

			const action = actionElement.dataset.action;
			const index = Number.parseInt(actionElement.dataset.index || '-1', 10);

			if (!action || index < 0 || !timelineData[index]) {
				return;
			}

			if (action === 'pin') {
				event.preventDefault();
				timelineData[index].pinned = !Boolean(timelineData[index].pinned);
				saveToLocal();
				renderTimeline();
				return;
			}

			if (action === 'edit') {
				const eventItem = timelineData[index];
				editIndexInput.value = String(index);
				eventDateInput.value = eventItem.date;
				eventShowDayInput.checked = eventItem.showDay !== false;
				eventShowMonthInput.checked = eventItem.showMonth !== false;
				eventUseCustomYearInput.checked = Boolean(eventItem.useCustomYear);
				eventCustomYearInput.value = Number.isInteger(eventItem.customYear)
					? String(eventItem.customYear)
					: '';
				eventEraTagInput.value = ['none', 'christian', 'common-era'].includes(eventItem.eraTag)
					? eventItem.eraTag
					: 'none';
				updateDateModeUI();
				eventTitleInput.value = eventItem.title;
				eventTextInput.value = eventItem.text;
				eventImageInput.value = '';
				removeImageOnSave = false;
				eventImageName.textContent = eventItem.imageData ? 'Immagine già presente' : 'Nessun file selezionato';
				if (eventItem.imageData) {
					hideImagePreview();
					showImagePreview(eventItem.imageData);
					removeEventImageBtn.classList.remove('hidden');
				} else {
					hideImagePreview();
					removeEventImageBtn.classList.add('hidden');
				}
				saveEventBtn.textContent = 'Aggiorna evento';
				modalTitle.textContent = 'Modifica evento';
				openModal();
				showStatus('Modalità modifica attiva: aggiorna e salva.');
			}

			if (action === 'delete') {
				timelineData.splice(index, 1);
				saveToLocal();
				renderTimeline();
				resetForm();
				showStatus('Evento eliminato.');
			}
		});

		downloadBtn.addEventListener('click', () => {
			closeBackupMenu();
			downloadTimelineData();
		});

		uploadInput.addEventListener('change', async () => {
			if (isViewerMode()) {
				uploadInput.value = '';
				return;
			}

			const file = uploadInput.files[0];
			if (!file) {
				return;
			}

			try {
				const text = await file.text();
				const parsed = JSON.parse(text);
				const importedEvents = Array.isArray(parsed) ? parsed : parsed.events;

				if (!Array.isArray(importedEvents)) {
					throw new Error('Il file non contiene una timeline valida.');
				}

				timelineData = normalizeImportedEvents(importedEvents);

				await saveToLocal();
				renderTimeline();
				resetForm();
				showStatus('File importato correttamente.');
			} catch (error) {
				showStatus(error.message || 'Errore durante l\'importazione.', true);
			} finally {
				uploadInput.value = '';
			}
		});

		eventImageInput.addEventListener('change', () => {
			const file = eventImageInput.files[0];
			eventImageName.textContent = file ? `Immagine selezionata: ${file.name}` : 'Nessun file selezionato';
			removeImageOnSave = false;
			if (file || Number.parseInt(editIndexInput.value, 10) >= 0) {
				removeEventImageBtn.classList.remove('hidden');
			} else {
				removeEventImageBtn.classList.add('hidden');
			}
			showImagePreviewFromFile(file);
		});

		eventImageTrigger.addEventListener('click', () => {
			eventImageInput.value = '';
		});

		eventUseCustomYearInput.addEventListener('change', () => {
			updateDateModeUI();
			if (!eventUseCustomYearInput.checked) {
				eventCustomYearInput.value = '';
			}
		});

		removeEventImageBtn.addEventListener('click', () => {
			eventImageInput.value = '';
			removeImageOnSave = Number.parseInt(editIndexInput.value, 10) >= 0;
			eventImageName.textContent = removeImageOnSave
				? 'Immagine rimossa (salva per confermare)'
				: 'Nessun file selezionato';
			hideImagePreview();
			if (!removeImageOnSave) {
				removeEventImageBtn.classList.add('hidden');
			}
		});

		(async function init() {
			applyTheme(loadTheme());
			applyAppMode();
			updateFullscreenState();
			updateDateModeUI();
			if (!SHARED_TIMELINE_PAYLOAD) {
				setTimelineTitle(loadTimelineTitle());
			}
			await loadFromLocal();
			renderTimeline();
			updateTimelineLineWidth();
			showStatus('Pronto. I dati vengono salvati nel browser.');
		})();
	</script>
</body>
</html>
