/* FIXIT - interaksi umum */
(function () {
    'use strict';

    // Navigasi seluler
    var navToggle = document.querySelector('[data-nav-toggle]');
    var nav = document.getElementById('site-nav');
    if (navToggle && nav) {
        navToggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Tutup pesan flash
    document.querySelectorAll('[data-flash-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            var flash = button.closest('[data-flash]');
            if (flash) {
                flash.remove();
            }
        });
    });

    document.querySelectorAll('[data-flash]').forEach(function (flash) {
        window.setTimeout(function () {
            flash.style.transition = 'opacity .3s ease';
            flash.style.opacity = '0';
            window.setTimeout(function () { flash.remove(); }, 320);
        }, 7000);
    });

    // Konfirmasi hapus
    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm') || 'Yakin ingin melanjutkan?')) {
                event.preventDefault();
            }
        });
    });

    // Tampilkan / sembunyikan kata sandi
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-password-toggle'));
            if (!input) {
                return;
            }
            var tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            button.textContent = tampil ? 'Sembunyikan' : 'Tampilkan';
        });
    });

    // Isi slug otomatis dari judul
    document.querySelectorAll('[data-slug-source]').forEach(function (input) {
        var target = document.getElementById(input.getAttribute('data-slug-source'));
        if (!target) {
            return;
        }
        input.addEventListener('input', function () {
            if (target.dataset.touched === '1') {
                return;
            }
            target.value = input.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        });
        target.addEventListener('input', function () {
            target.dataset.touched = '1';
        });
    });

    // Salin ringkasan diagnosis
    document.querySelectorAll('[data-copy-summary]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.getAttribute('data-copy-target') || '');
            if (!target) {
                return;
            }

            var status = document.querySelector('[data-copy-status]');
            var teks = target.value || target.textContent || '';

            function selesai(pesan) {
                if (!status) {
                    return;
                }
                status.textContent = pesan;
                window.setTimeout(function () {
                    status.textContent = '';
                }, 4000);
            }

            function salinManual() {
                target.hidden = false;
                target.select();
                try {
                    document.execCommand('copy');
                    selesai('Ringkasan disalin.');
                } catch (e) {
                    selesai('Gagal menyalin. Pilih teks secara manual.');
                }
                target.hidden = true;
                if (window.getSelection) {
                    window.getSelection().removeAllRanges();
                }
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(teks).then(function () {
                    selesai('Ringkasan disalin.');
                }).catch(salinManual);
            } else {
                salinManual();
            }
        });
    });

    // ---------------------------------------------------------
    // Halaman diagnosis
    // ---------------------------------------------------------
    var form = document.getElementById('form-diagnosis');
    if (form) {
        var search = document.getElementById('symptom-search');
        var items = Array.prototype.slice.call(form.querySelectorAll('[data-symptom-item]'));
        var groups = Array.prototype.slice.call(form.querySelectorAll('[data-cat-group]'));
        var tabs = Array.prototype.slice.call(form.querySelectorAll('[data-cat]'));
        var countEl = form.querySelector('[data-count]');
        var listEl = form.querySelector('[data-selected-list]');
        var submitBtn = form.querySelector('[data-submit]');
        var emptyNote = form.querySelector('[data-selected-empty]');
        var activeTab = form.querySelector('[data-cat].is-active');
        var activeCat = activeTab ? activeTab.getAttribute('data-cat') : 'all';
        var labels = {};

        items.forEach(function (item) {
            labels[item.getAttribute('data-symptom-id')] = item.getAttribute('data-label');
        });

        function isChecked(item) {
            var box = item.querySelector('input[type="checkbox"]');
            return box ? box.checked : false;
        }

        function visible(item) {
            var cocokKategori = activeCat === 'all' || item.getAttribute('data-cat') === activeCat;
            var kata = (search && search.value ? search.value : '').toLowerCase().trim();
            var cocokKata = !kata || item.getAttribute('data-label').toLowerCase().indexOf(kata) !== -1;
            return cocokKategori && cocokKata;
        }

        function applyFilter() {
            groups.forEach(function (group) {
                var ada = false;
                group.querySelectorAll('[data-symptom-item]').forEach(function (item) {
                    var tampil = visible(item);
                    item.hidden = !tampil;
                    if (tampil) {
                        ada = true;
                    }
                });
                group.hidden = !ada;
            });
        }

        function refresh() {
            var terpilih = items.filter(isChecked);
            if (countEl) {
                countEl.textContent = String(terpilih.length);
            }
            if (submitBtn) {
                submitBtn.disabled = terpilih.length === 0;
            }
            if (!listEl) {
                return;
            }
            listEl.innerHTML = '';
            if (emptyNote) {
                emptyNote.hidden = terpilih.length > 0;
            }
            terpilih.forEach(function (item) {
                var id = item.getAttribute('data-symptom-id');
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'selected-chip';
                chip.innerHTML = '';
                var teks = document.createElement('span');
                teks.textContent = labels[id];
                var silang = document.createElement('span');
                silang.setAttribute('aria-hidden', 'true');
                silang.textContent = '\u00d7';
                chip.appendChild(teks);
                chip.appendChild(silang);
                chip.addEventListener('click', function () {
                    var box = item.querySelector('input[type="checkbox"]');
                    if (box) {
                        box.checked = false;
                        refresh();
                    }
                });
                listEl.appendChild(chip);
            });
        }

        items.forEach(function (item) {
            var box = item.querySelector('input[type="checkbox"]');
            if (box) {
                box.addEventListener('change', refresh);
            }
        });

        if (search) {
            search.addEventListener('input', applyFilter);
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activeCat = tab.getAttribute('data-cat');
                tabs.forEach(function (lain) {
                    lain.classList.toggle('is-active', lain === tab);
                });
                applyFilter();
            });
        });

        var selectVisible = form.querySelector('[data-select-visible]');
        if (selectVisible) {
            selectVisible.addEventListener('click', function () {
                items.forEach(function (item) {
                    if (!item.hidden) {
                        var box = item.querySelector('input[type="checkbox"]');
                        if (box) {
                            box.checked = true;
                        }
                    }
                });
                refresh();
            });
        }

        var clearAll = form.querySelector('[data-clear-all]');
        if (clearAll) {
            clearAll.addEventListener('click', function () {
                items.forEach(function (item) {
                    var box = item.querySelector('input[type="checkbox"]');
                    if (box) {
                        box.checked = false;
                    }
                });
                refresh();
            });
        }

        form.addEventListener('submit', function (event) {
            if (!items.some(isChecked)) {
                event.preventDefault();
                return;
            }
        });

        applyFilter();
        refresh();
    }

    // Beri tanda bentuk gambar artikel
    document.querySelectorAll('[data-file-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var preview = document.getElementById(input.getAttribute('data-file-preview'));
            if (!preview || !input.files || !input.files[0]) {
                return;
            }
            preview.src = URL.createObjectURL(input.files[0]);
            preview.hidden = false;
        });
    });
})();
