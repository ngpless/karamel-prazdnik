/* Карамель: небольшие улучшения интерфейса. Сайт полностью работает и без JavaScript. */
(function () {
    'use strict';

    // Мобильное меню
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('mainnav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Ротация баннеров: автосмена каждые 6 секунд, пауза при наведении и фокусе, кнопки и точки
    var box = document.querySelector('[data-carousel]');
    if (!box) { return; }
    var slides = Array.prototype.slice.call(box.querySelectorAll('[data-slide]'));
    var ctl = box.querySelector('.carousel-ctl');
    if (slides.length < 2) { return; }
    var dots = ctl ? Array.prototype.slice.call(ctl.querySelectorAll('.c-dot')) : [];
    var idx = 0, timer = null, paused = false;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function show(i) {
        idx = (i + slides.length) % slides.length;
        slides.forEach(function (s, k) { s.classList.toggle('is-current', k === idx); });
        dots.forEach(function (d, k) { d.classList.toggle('on', k === idx); });
    }
    function start() { stop(); if (!paused && !reduce) { timer = setInterval(function () { show(idx + 1); }, 6000); } }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    if (ctl) {
        ctl.querySelector('.c-prev').addEventListener('click', function () { show(idx - 1); start(); });
        ctl.querySelector('.c-next').addEventListener('click', function () { show(idx + 1); start(); });
        dots.forEach(function (d, k) { d.addEventListener('click', function () { show(k); start(); }); });
        var pauseBtn = ctl.querySelector('.c-pause');
        pauseBtn.addEventListener('click', function () {
            paused = !paused;
            pauseBtn.textContent = paused ? '▶' : 'II';
            pauseBtn.setAttribute('aria-label', paused ? 'Возобновить смену баннеров' : 'Остановить смену баннеров');
            if (paused) { stop(); } else { start(); }
        });
    }
    box.addEventListener('mouseenter', stop);
    box.addEventListener('mouseleave', start);
    box.addEventListener('focusin', stop);
    box.addEventListener('focusout', start);
    start();
})();
