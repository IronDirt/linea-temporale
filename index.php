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
			<p class="subtitle">Crea la tua linea temporale personalizzata</p>
		</div>

		<section class="card timeline-section">
			<div class="timeline-topbar">
				<div class="timeline-header">
					<h2 id="timelineTitle">Timeline</h2>
					<button type="button" class="muted timeline-title-edit" id="editTimelineTitleBtn" aria-label="Modifica titolo timeline" title="Modifica titolo">✎</button>
				</div>
				<div class="timeline-zoom-controls" aria-label="Controlli zoom timeline">
					<button type="button" class="muted timeline-zoom-btn" id="zoomOutBtn" aria-label="Riduci dettagli timeline" title="Riduci dettagli">−</button>
					<button type="button" class="muted timeline-zoom-btn" id="zoomInBtn" aria-label="Aumenta dettagli timeline" title="Aumenta dettagli">+</button>
				</div>
			</div>
			<div id="timeline" class="timeline"></div>
		</section>
	</div>

	<div class="fab-stack">
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
				<button type="button" class="secondary" id="downloadBtn" role="menuitem">Scarica</button>
				<button type="button" class="secondary" id="uploadBtn" role="menuitem">Importa</button>
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
					<button type="button" class="muted" id="clearFormBtn">Pulisci</button>
				</div>
			</form>
		</section>
	</div>

	<script>
		const STORAGE_KEY = 'timeline_app_data_v1';
		const THEME_KEY = 'timeline_theme_v1';
		const TIMELINE_TITLE_KEY = 'timeline_title_v1';
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
		const clearFormBtn = document.getElementById('clearFormBtn');
		const saveEventBtn = document.getElementById('saveEventBtn');
		const fullscreenBtn = document.getElementById('fullscreenBtn');
		const fullscreenEnterIcon = document.getElementById('fullscreenEnterIcon');
		const fullscreenExitIcon = document.getElementById('fullscreenExitIcon');
		const themeToggleBtn = document.getElementById('themeToggleBtn');
		const themeMoonIcon = document.getElementById('themeMoonIcon');
		const themeSunIcon = document.getElementById('themeSunIcon');
		const downloadBtn = document.getElementById('downloadBtn');
		const uploadBtn = document.getElementById('uploadBtn');
		const uploadInput = document.getElementById('uploadInput');
		const timelineEl = document.getElementById('timeline');
		const timelineTitleEl = document.getElementById('timelineTitle');
		const editTimelineTitleBtn = document.getElementById('editTimelineTitleBtn');
		const zoomOutBtn = document.getElementById('zoomOutBtn');
		const zoomInBtn = document.getElementById('zoomInBtn');
		const openFormBtn = document.getElementById('openFormBtn');
		const backupMenuBtn = document.getElementById('backupMenuBtn');
		const backupMenu = document.getElementById('backupMenu');
		const eventModal = document.getElementById('eventModal');
		const closeModalBtn = document.getElementById('closeModalBtn');
		const modalBackdrop = document.getElementById('modalBackdrop');
		const modalTitle = document.getElementById('modalTitle');

		let timelineData = [];
		let currentTheme = 'light';
		let zoomLevel = 0;
		let imagePreviewObjectUrl = '';
		let removeImageOnSave = false;

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
			document.body.classList.toggle('presentation-mode', isFullscreen);
			fullscreenEnterIcon.classList.toggle('hidden', isFullscreen);
			fullscreenExitIcon.classList.toggle('hidden', !isFullscreen);
			fullscreenBtn.title = isFullscreen ? 'Esci da schermo intero' : 'Attiva schermo intero';
			fullscreenBtn.setAttribute('aria-label', isFullscreen ? 'Esci da schermo intero' : 'Attiva schermo intero');
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
						<h3 class="timeline-title">${escapeHtml(eventItem.title)}</h3>
						${imageBlock}
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

		clearFormBtn.addEventListener('click', () => {
			resetForm();
			showStatus('Form pulito.');
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

		themeToggleBtn.addEventListener('click', () => {
			const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
			applyTheme(nextTheme);
			localStorage.setItem(THEME_KEY, nextTheme);
		});

		zoomOutBtn.addEventListener('click', () => {
			if (!timelineData.length) {
				return;
			}
			zoomLevel = Math.min(zoomLevel + 1, timelineData.length - 1);
			applyZoomToTimeline({ preserveFocus: true, smooth: true });
		});

		zoomInBtn.addEventListener('click', () => {
			zoomLevel = Math.max(zoomLevel - 1, 0);
			applyZoomToTimeline({ preserveFocus: true, smooth: true });
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
			try {
				if (!document.fullscreenElement) {
					await document.documentElement.requestFullscreen();
				} else {
					await document.exitFullscreen();
				}
			} catch (error) {
				console.error('Errore modalità schermo intero:', error);
			}
		});

		document.addEventListener('fullscreenchange', updateFullscreenState);
		window.addEventListener('resize', updateTimelineLineWidth);

		closeModalBtn.addEventListener('click', closeModal);
		modalBackdrop.addEventListener('click', closeModal);

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && !eventModal.classList.contains('hidden')) {
				closeModal();
			}

			if (event.key === 'Escape') {
				closeBackupMenu();
			}
		});

		document.addEventListener('click', (event) => {
			if (!(event.target instanceof HTMLElement)) {
				return;
			}

			if (!backupMenu.contains(event.target) && event.target !== backupMenuBtn) {
				closeBackupMenu();
			}
		});

		timelineEl.addEventListener('click', (event) => {
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
		});

		uploadInput.addEventListener('change', async () => {
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

				timelineData = importedEvents.map((item) => ({
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
			updateFullscreenState();
			updateDateModeUI();
			setTimelineTitle(loadTimelineTitle());
			await loadFromLocal();
			renderTimeline();
			updateTimelineLineWidth();
			showStatus('Pronto. I dati vengono salvati nel browser.');
		})();
	</script>
</body>
</html>
