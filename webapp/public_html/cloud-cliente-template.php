<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-custom { max-width: 900px; margin: 0 auto; }
        .header-cloud { text-align: center; color: #fff; margin-bottom: 40px; }
        .header-cloud i {
            font-size: 3.5rem;
            margin-bottom: 15px;
            display: block;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .header-cloud h1 { font-size: 2.2rem; font-weight: 700; margin: 0 0 10px; }
        .header-cloud p { font-size: 1.1rem; opacity: 0.95; margin: 0; }
        .card-shell {
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        .stats-box {
            background: rgba(255,255,255,0.15);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            backdrop-filter: blur(10px);
        }
        .stat-item { text-align: center; }
        .stat-number { font-size: 2rem; font-weight: 700; display: block; }
        .stat-label { font-size: 0.95rem; opacity: 0.9; margin-top: 5px; }
        .file-item { border-left: 5px solid #667eea; transition: all 0.3s ease; }
        .file-item:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .file-icon {
            font-size: 2.5rem;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f8f9fa;
            flex-shrink: 0;
            color: #667eea;
        }
        .file-icon.audio { color: #198754; }
        .file-info { flex: 1; min-width: 0; }
        .file-name { font-weight: 600; color: #333; margin-bottom: 5px; word-break: break-word; }
        .file-meta { font-size: 0.9rem; color: #666; }
        .file-nota {
            margin-top: 8px;
            padding: 8px;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #555;
            border-left: 3px solid #667eea;
        }
        .btn-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .btn-actions button,
        .btn-actions a {
            padding: 6px 12px;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .empty-state,
        .error-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state { color: #999; }
        .error-state { color: #5c2b29; }
        .empty-state i,
        .error-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        footer {
            text-align: center;
            color: white;
            margin-top: 50px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        @media (max-width: 768px) {
            .header-cloud h1 { font-size: 1.8rem; }
            .file-icon { width: 50px; height: 50px; font-size: 1.8rem; }
            .btn-actions { flex-direction: column; width: 100%; }
            .btn-actions button,
            .btn-actions a { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <div class="header-cloud">
            <i class="fas fa-cloud"></i>
            <h1>Cloud Storage</h1>
            <p><?= $cliente_nome !== '' ? h($cliente_nome) : 'Area materiali condivisi' ?></p>
        </div>

        <?php if ($error_message === ''): ?>
        <div class="stats-box">
            <div class="stat-item">
                <span class="stat-number"><?= (int) $file_count ?></span>
                <span class="stat-label">File</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= h($total_size_human) ?></span>
                <span class="stat-label">Totale</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">✓</span>
                <span class="stat-label">Disponibile</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="card card-shell">
            <div class="card-body p-4">
                <?php if ($error_message !== ''): ?>
                <div class="error-state">
                    <i class="fas fa-circle-exclamation text-danger"></i>
                    <h5 class="mb-3"><?= h($error_title !== '' ? $error_title : 'Errore') ?></h5>
                    <p class="text-muted mb-0"><?= h($error_message) ?></p>
                </div>
                <?php else: ?>
                <h5 class="mb-4">
                    <i class="fas fa-folder-open me-2"></i>I tuoi materiali
                </h5>

                <?php if (empty($files)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>Nessun file disponibile</h5>
                    <p class="text-muted">I materiali compariranno qui appena saranno caricati.</p>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($files as $file): ?>
                    <div class="col-12">
                        <div class="card file-item mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex gap-3 align-items-start flex-wrap">
                                    <div class="file-icon<?= !empty($file['is_audio']) ? ' audio' : '' ?>">
                                        <i class="fas <?= h($file['icon'] ?? 'fa-file') ?>"></i>
                                    </div>

                                    <div class="file-info">
                                        <div class="file-name"><?= h($file['nome_originale'] ?? '') ?></div>
                                        <div class="file-meta">
                                            <i class="fas fa-database me-1"></i><?= h($file['dimensione_human'] ?? '0 B') ?>
                                            <?php if (!empty($file['created_at_human'])): ?>
                                             • <i class="fas fa-calendar me-1"></i><?= h($file['created_at_human']) ?>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($file['nota'])): ?>
                                        <div class="file-nota">
                                            <i class="fas fa-sticky-note me-2"></i><?= h($file['nota']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="btn-actions ms-auto">
                                        <?php if (!empty($file['is_audio'])): ?>
                                        <button class="btn btn-sm btn-outline-success play-audio"
                                                type="button"
                                                data-file-id="<?= (int) $file['id'] ?>"
                                                data-file-name="<?= h($file['nome_originale'] ?? '') ?>">
                                            <i class="fas fa-play me-1"></i>Riproduci
                                        </button>
                                        <?php endif; ?>

                                        <a href="?hash=<?= rawurlencode($hash) ?>&amp;action=download&amp;file_id=<?= (int) $file['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download me-1"></i>Scarica
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <footer>
            <p><i class="fas fa-lock me-2"></i>Accesso protetto e privato</p>
        </footer>
    </div>

    <div class="modal fade" id="audioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-music me-2"></i>Riproduci Audio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <p id="audioModalTitle" class="fw-semibold text-center mb-3"></p>
                    <audio controls class="w-100" id="audioPlayer" style="border-radius: 8px;">
                        Il tuo browser non supporta l'elemento audio.
                    </audio>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                    <a id="downloadBtn" href="#" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Scarica
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const audioModalEl = document.getElementById('audioModal');
            const playButtons = document.querySelectorAll('.play-audio');
            if (!audioModalEl || playButtons.length === 0) {
                return;
            }

            const audioModal = new bootstrap.Modal(audioModalEl);
            const audioPlayer = document.getElementById('audioPlayer');
            const audioModalTitle = document.getElementById('audioModalTitle');
            const downloadBtn = document.getElementById('downloadBtn');
            // The controller validates $hash before rendering this template.
            const streamBaseUrl = <?= json_encode('?hash=' . rawurlencode($hash) . '&action=get_file&file_id=') ?>;
            const downloadBaseUrl = <?= json_encode('?hash=' . rawurlencode($hash) . '&action=download&file_id=') ?>;

            playButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const fileId = button.dataset.fileId || '';
                    audioModalTitle.textContent = button.dataset.fileName || 'Riproduzione audio';
                    audioPlayer.src = streamBaseUrl + fileId;
                    downloadBtn.href = downloadBaseUrl + fileId;
                    audioPlayer.load();
                    audioModal.show();
                });
            });

            audioModalEl.addEventListener('hide.bs.modal', () => {
                audioPlayer.pause();
                audioPlayer.src = '';
                downloadBtn.href = '#';
            });
        })();
    </script>
</body>
</html>
