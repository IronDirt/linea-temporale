<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Linea Temporale Locale</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
	<div class="container">
		<div class="top-content">
			<h1>Linea Temporale</h1>
			<p class="subtitle">Aggiungi data, testo e immagine. Le modifiche restano nel browser e puoi esportare/importare un file JSON.</p>

			<div class="toolbar top-toolbar">
				<button type="button" class="muted" id="themeToggleBtn">Tema: Dark</button>
				<button type="button" class="danger" id="resetAllBtn">Svuota tutto</button>
			</div>

			<p class="status" id="statusMsg"></p>
		</div>

		<section class="card timeline-section">
			<h2>Timeline</h2>
			<div id="timeline" class="timeline"></div>
		</section>
	</div>

	<div class="fab-stack">
		<button type="button" class="fab-add" id="openFormBtn" aria-label="Aggiungi nuovo evento">+</button>
		<button type="button" class="fab-backup" id="backupMenuBtn" aria-label="Backup" title="Backup">⤓</button>
		<div class="backup-menu hidden" id="backupMenu" role="menu" aria-label="Menu backup">
			<button type="button" class="secondary" id="downloadBtn" role="menuitem">Scarica</button>
			<button type="button" class="secondary" id="uploadBtn" role="menuitem">Importa</button>
		</div>
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

				<label for="eventTitle">Titolo</label>
				<input id="eventTitle" type="text" placeholder="Es. Inizio progetto" required>

				<label for="eventText">Testo</label>
				<textarea id="eventText" placeholder="Descrizione evento" required></textarea>

				<label for="eventImage">Immagine (opzionale)</label>
				<input id="eventImage" type="file" accept="image/*">

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
		const CACHE_NAME = 'timeline_app_cache_v1';
		const CACHE_URL = '/timeline-data.json';
		const IMAGE_MAX_WIDTH = 1600;
		const IMAGE_MAX_HEIGHT = 1600;
		const IMAGE_QUALITY = 0.8;

		const eventForm = document.getElementById('eventForm');
		const editIndexInput = document.getElementById('editIndex');
		const eventDateInput = document.getElementById('eventDate');
		const eventTitleInput = document.getElementById('eventTitle');
		const eventTextInput = document.getElementById('eventText');
		const eventImageInput = document.getElementById('eventImage');
		const clearFormBtn = document.getElementById('clearFormBtn');
		const saveEventBtn = document.getElementById('saveEventBtn');
		const resetAllBtn = document.getElementById('resetAllBtn');
		const themeToggleBtn = document.getElementById('themeToggleBtn');
		const downloadBtn = document.getElementById('downloadBtn');
		const uploadBtn = document.getElementById('uploadBtn');
		const uploadInput = document.getElementById('uploadInput');
		const timelineEl = document.getElementById('timeline');
		const statusMsg = document.getElementById('statusMsg');
		const openFormBtn = document.getElementById('openFormBtn');
		const backupMenuBtn = document.getElementById('backupMenuBtn');
		const backupMenu = document.getElementById('backupMenu');
		const eventModal = document.getElementById('eventModal');
		const closeModalBtn = document.getElementById('closeModalBtn');
		const modalBackdrop = document.getElementById('modalBackdrop');
		const modalTitle = document.getElementById('modalTitle');

		let timelineData = [];
		let currentTheme = 'light';

		function applyTheme(theme) {
			currentTheme = theme === 'dark' ? 'dark' : 'light';
			document.body.classList.toggle('theme-dark', currentTheme === 'dark');
			themeToggleBtn.textContent = currentTheme === 'dark' ? 'Tema: White' : 'Tema: Dark';
		}

		function loadTheme() {
			const savedTheme = localStorage.getItem(THEME_KEY);
			if (savedTheme === 'light' || savedTheme === 'dark') {
				return savedTheme;
			}

			const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			return prefersDark ? 'dark' : 'light';
		}

		function showStatus(message, isError = false) {
			statusMsg.textContent = message;
			statusMsg.style.color = isError ? 'var(--status-error)' : 'var(--status-success)';
		}

		function formatDate(isoDate) {
			if (!isoDate) {
				return '';
			}
			const date = new Date(isoDate + 'T00:00:00');
			if (Number.isNaN(date.getTime())) {
				return isoDate;
			}
			return date.toLocaleDateString('it-IT', {
				year: 'numeric',
				month: 'long',
				day: 'numeric'
			});
		}

		function renderTimeline() {
			if (!timelineData.length) {
				timelineEl.innerHTML = '<p class="empty">Nessun evento inserito.</p>';
				return;
			}

			const sorted = [...timelineData].sort((a, b) => a.date.localeCompare(b.date));
			timelineEl.innerHTML = '';

			sorted.forEach((eventItem) => {
				const originalIndex = timelineData.findIndex((item) => item.id === eventItem.id);

				const item = document.createElement('article');
				item.className = 'timeline-item';

				const imageBlock = eventItem.imageData
					? `<img class="timeline-image" src="${eventItem.imageData}" alt="${escapeHtml(eventItem.title)}">`
					: '';

				item.innerHTML = `
					<div class="timeline-date">${formatDate(eventItem.date)}</div>
					<h3 class="timeline-title">${escapeHtml(eventItem.title)}</h3>
					${imageBlock}
					<p class="timeline-text">${escapeHtml(eventItem.text)}</p>
					<div class="item-actions">
						<button type="button" class="secondary" data-action="edit" data-index="${originalIndex}">Modifica</button>
						<button type="button" class="danger" data-action="delete" data-index="${originalIndex}">Elimina</button>
					</div>
				`;

				timelineEl.appendChild(item);
			});
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
			eventTitleInput.value = '';
			eventTextInput.value = '';
			eventImageInput.value = '';
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
			const title = eventTitleInput.value.trim();
			const text = eventTextInput.value.trim();

			if (!date || !title || !text) {
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
				timelineData[editIndex] = {
					...previous,
					date,
					title,
					text,
					imageData: imageData ?? previous.imageData
				};
				showStatus('Evento aggiornato con successo.');
			} else {
				timelineData.push({
					id: crypto.randomUUID(),
					date,
					title,
					text,
					imageData
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

			const action = target.dataset.action;
			const index = Number.parseInt(target.dataset.index || '-1', 10);

			if (!action || index < 0 || !timelineData[index]) {
				return;
			}

			if (action === 'edit') {
				const eventItem = timelineData[index];
				editIndexInput.value = String(index);
				eventDateInput.value = eventItem.date;
				eventTitleInput.value = eventItem.title;
				eventTextInput.value = eventItem.text;
				eventImageInput.value = '';
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

		resetAllBtn.addEventListener('click', async () => {
			const confirmReset = window.confirm('Vuoi eliminare tutti gli eventi?');
			if (!confirmReset) {
				return;
			}

			timelineData = [];
			resetForm();
			await saveToLocal();
			renderTimeline();
			showStatus('Timeline svuotata.');
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
					title: typeof item.title === 'string' ? item.title : '',
					text: typeof item.text === 'string' ? item.text : '',
					imageData: typeof item.imageData === 'string' ? item.imageData : null
				})).filter((item) => item.date && item.title && item.text);

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

		(async function init() {
			applyTheme(loadTheme());
			await loadFromLocal();
			renderTimeline();
			showStatus('Pronto. I dati vengono salvati nel browser.');
		})();
	</script>
</body>
</html>
