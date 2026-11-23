/* FIXIT - antarmuka chatbot */
(function () {
    'use strict';

    var shell = document.getElementById('chat-shell');
    if (!shell) {
        return;
    }

    var apiUrl = shell.getAttribute('data-api');
    var loginUrl = shell.getAttribute('data-login');
    var riwayatUrl = shell.getAttribute('data-riwayat');
    var token = shell.getAttribute('data-token') || '';
    var messagesEl = document.getElementById('chat-messages');
    var bodyEl = document.getElementById('chat-body');
    var form = document.getElementById('chat-form');
    var input = document.getElementById('chat-input');
    var progressEl = document.getElementById('chat-progress');
    var sending = false;

    function scrollBawah() {
        if (bodyEl) {
            bodyEl.scrollTop = bodyEl.scrollHeight;
        }
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined && text !== null) {
            node.textContent = text;
        }
        return node;
    }

    function bubblePesan(msg) {
        var wrap = el('div', 'msg msg-' + msg.from);
        var avatar = el('span', 'msg-avatar', msg.from === 'bot' ? 'F' : 'A');
        avatar.setAttribute('aria-hidden', 'true');
        var bubble = el('div', 'bubble', msg.text);
        wrap.appendChild(avatar);
        wrap.appendChild(bubble);

        if (msg.quick && msg.quick.length) {
            var quick = el('div', 'quick-replies');
            msg.quick.forEach(function (item) {
                var chip = el('button', 'quick-chip', item.label);
                chip.type = 'button';
                if (item.symptom_id) {
                    chip.setAttribute('data-symptom', String(item.symptom_id));
                } else if (item.category_id) {
                    chip.setAttribute('data-category', String(item.category_id));
                } else {
                    chip.setAttribute('data-text', item.text || item.label);
                }
                quick.appendChild(chip);
            });
            bubble.appendChild(quick);
        }

        return wrap;
    }

    function kartuHasil(result) {
        var card = el('div', 'chat-result');
        card.appendChild(el('div', 'chat-result-head', 'Kemungkinan penyebab'));

        result.items.forEach(function (item, index) {
            var row = el('div', 'chat-result-item');
            var top = el('div', 'chat-result-item-top');
            top.appendChild(el('strong', '', (index + 1) + '. ' + item.nama_penyebab));
            top.appendChild(el('span', '', Math.round(Number(item.skor) * 100) + '%'));
            row.appendChild(top);

            if (item.solusi && item.solusi.length) {
                var list = el('ol');
                item.solusi.slice(0, 3).forEach(function (langkah) {
                    list.appendChild(el('li', '', langkah.langkah));
                });
                if (item.solusi.length > 3) {
                    var more = el('li', '', 'Lihat semua langkah di halaman hasil diagnosis.');
                    more.style.fontStyle = 'italic';
                    list.appendChild(more);
                }
                row.appendChild(list);
            }

            card.appendChild(row);
        });

        var foot = el('div', 'chat-result-foot');
        if (result.diagnosis_id) {
            var saved = el('span', '', 'Hasil tersimpan di riwayat. ');
            foot.appendChild(saved);
            if (riwayatUrl) {
                var link = el('a', '', 'Lihat riwayat');
                link.href = riwayatUrl + '?id=' + encodeURIComponent(result.diagnosis_id);
                foot.appendChild(link);
            }
        } else if (loginUrl) {
            foot.appendChild(el('span', '', 'Masuk untuk menyimpan hasil ini ke riwayat. '));
            var login = el('a', '', 'Masuk');
            login.href = loginUrl;
            foot.appendChild(login);
        }
        if (result.gejala && result.gejala.length) {
            var gejala = el('div', 'symptom-tags');
            result.gejala.forEach(function (teks) {
                gejala.appendChild(el('span', 'symptom-tag', teks));
            });
            foot.appendChild(gejala);
        }
        card.appendChild(foot);
        return card;
    }

    function render(state) {
        messagesEl.innerHTML = '';
        (state.messages || []).forEach(function (msg) {
            messagesEl.appendChild(bubblePesan(msg));
        });
        if (state.result) {
            messagesEl.appendChild(kartuHasil(state.result));
        }
        if (progressEl) {
            progressEl.textContent = state.done
                ? 'Selesai'
                : 'Pertanyaan ' + (state.progress || '0/5');
        }
        scrollBawah();
    }

    function tampilkanMengetik() {
        var wrap = el('div', 'msg msg-bot');
        var avatar = el('span', 'msg-avatar', 'F');
        avatar.setAttribute('aria-hidden', 'true');
        var bubble = el('div', 'bubble');
        var typing = el('span', 'typing');
        typing.appendChild(el('span'));
        typing.appendChild(el('span'));
        typing.appendChild(el('span'));
        bubble.appendChild(typing);
        wrap.appendChild(avatar);
        wrap.appendChild(bubble);
        messagesEl.appendChild(wrap);
        scrollBawah();
        return wrap;
    }

    function kirim(payload, userText) {
        if (sending) {
            return;
        }
        sending = true;

        if (userText) {
            messagesEl.appendChild(bubblePesan({ from: 'user', text: userText, quick: [] }));
            scrollBawah();
        }
        var typing = tampilkanMengetik();

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': token
            },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.ok) {
                        throw new Error(data.error || 'Gagal menghubungi asisten.');
                    }
                    return data.data;
                });
            })
            .then(function (state) {
                render(state);
            })
            .catch(function (error) {
                if (typing && typing.parentNode) {
                    typing.remove();
                }
                messagesEl.appendChild(bubblePesan({
                    from: 'bot',
                    text: 'Maaf, koneksi ke asisten terganggu. Coba kirim pesan sekali lagi.',
                    quick: []
                }));
                scrollBawah();
            })
            .finally(function () {
                sending = false;
                if (input) {
                    input.focus();
                }
            });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var teks = input ? input.value.trim() : '';
            if (!teks) {
                return;
            }
            if (input) {
                input.value = '';
            }
            kirim({ message: teks }, teks);
        });
    }

    if (messagesEl) {
        messagesEl.addEventListener('click', function (event) {
            var chip = event.target.closest('.quick-chip');
            if (!chip) {
                return;
            }
            if (chip.hasAttribute('data-symptom')) {
                var id = chip.getAttribute('data-symptom');
                kirim({ symptom_id: Number(id) }, chip.textContent);
            } else if (chip.hasAttribute('data-category')) {
                kirim({ category_id: Number(chip.getAttribute('data-category')) }, chip.textContent);
            } else {
                var teks = chip.getAttribute('data-text') || chip.textContent;
                kirim({ message: teks }, teks);
            }
        });
    }

    // Muat percakapan dari sesi
    fetch(apiUrl, {
        method: 'GET',
        headers: { 'X-CSRF-Token': token }
    })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.ok) {
                render(data.data);
            }
        })
        .catch(function () {
            if (messagesEl) {
                messagesEl.innerHTML = '';
                messagesEl.appendChild(bubblePesan({
                    from: 'bot',
                    text: 'Asisten belum dapat dimuat. Muat ulang halaman untuk mencoba lagi.',
                    quick: []
                }));
            }
        });
})();
