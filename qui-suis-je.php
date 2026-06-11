<?php

/**
 * Net Auditor IA — Qui suis-je ?
 * Agre Agency — Page de présentation autonome
 * Couleurs Slate-950 forcées, aucun dark: préfixe pour immunité thème.
 */
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#020617">
    <title>Qui suis-je ? — Agre Agency</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: #020617;
            color: #f1f5f9;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Grille de fond */
        .bg-grid {
            background-image:
                linear-gradient(rgba(56, 189, 248, .04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56, 189, 248, .04) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        /* Liseré néon */
        .neon-border {
            box-shadow: 0 0 0 1px rgba(56, 189, 248, .15), 0 0 40px rgba(56, 189, 248, .06);
        }

        /* Glow photo */
        .photo-glow {
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .3), 0 0 30px rgba(56, 189, 248, .15);
            transition: box-shadow .3s ease, transform .3s ease;
        }

        .photo-glow:hover {
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .6), 0 0 50px rgba(56, 189, 248, .25);
            transform: scale(1.05);
        }

        /* Liens contact */
        .contact-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: #94a3b8;
            font-size: .875rem;
            transition: color .2s ease;
            text-decoration: none;
        }

        .contact-link:hover {
            color: #38bdf8;
        }

        /* Séparateur animé */
        .separator {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(56, 189, 248, .4), transparent);
        }

        /* Tag compétence */
        .skill-tag {
            display: inline-block;
            padding: .25rem .75rem;
            background: rgba(56, 189, 248, .08);
            border: 1px solid rgba(56, 189, 248, .2);
            border-radius: .375rem;
            color: #7dd3fc;
            font-size: .75rem;
            font-weight: 600;
        }

        /* Fade up */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-1 {
            animation: fadeUp .4s .05s ease both;
        }

        .fade-2 {
            animation: fadeUp .4s .15s ease both;
        }

        .fade-3 {
            animation: fadeUp .4s .25s ease both;
        }

        .fade-4 {
            animation: fadeUp .4s .35s ease both;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #020617;
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #38bdf8;
        }

        /* ── Glow orbs ─────────────────────────────────────────────── */
        .glow-orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        #glow-cyan {
            width: 650px;
            height: 650px;
            top: -200px;
            left: -170px;
            background: radial-gradient(circle, rgba(6, 182, 212, .08) 0%, transparent 65%);
        }

        #glow-violet {
            width: 520px;
            height: 520px;
            top: 35%;
            right: -155px;
            background: radial-gradient(circle, rgba(124, 58, 237, .07) 0%, transparent 65%);
        }

        /* ── Expertise card spotlight ──────────────────────────────── */
        .spotlight-card {
            position: relative;
            overflow: hidden;
        }

        .spotlight-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(240px circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
                    rgba(56, 189, 248, .07),
                    transparent 70%);
            opacity: 0;
            transition: opacity .3s ease-out;
            pointer-events: none;
            z-index: 1;
        }

        .spotlight-card:hover::before {
            opacity: 1;
        }

        .spotlight-card>* {
            position: relative;
            z-index: 2;
        }

        /* ── Transitions 300ms ──────────────────────────────────────── */
        .contact-link {
            transition: color .3s ease-out;
        }

        .photo-glow {
            transition: box-shadow .3s ease-out, transform .3s ease-out;
        }
    </style>
</head>

<body class="bg-grid">

    <!-- Glow orbs -->
    <div id="glow-cyan" class="glow-orb" aria-hidden="true"></div>
    <div id="glow-violet" class="glow-orb" aria-hidden="true"></div>

    <!-- Navbar retour -->
    <nav class="sticky top-0 z-50 border-b border-slate-800 bg-slate-950/90 backdrop-blur-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-13 flex items-center gap-3 py-3">
            <a href="index.php"
                class="flex items-center gap-2 text-sm text-slate-400 hover:text-sky-400 transition-colors duration-200">
                <!-- Arrow left -->
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 flex-shrink-0">
                    <path fill-rule="evenodd"
                        d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z"
                        clip-rule="evenodd" />
                </svg>
                <span id="nav-back">Retour à l'outil</span>
            </a>
            <div class="flex-1"></div>
            <!-- Lang toggle -->
            <div class="flex items-center bg-slate-800 rounded-md p-0.5 gap-0.5">
                <button onclick="setLang('fr')" id="btn-fr"
                    class="text-xs font-bold px-2.5 py-1 rounded transition-all duration-150 text-slate-400">FR</button>
                <button onclick="setLang('en')" id="btn-en"
                    class="text-xs font-bold px-2.5 py-1 rounded transition-all duration-150 text-slate-400">EN</button>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-16 space-y-12">

        <!-- ── Hero identité ─────────────────────────────────────── -->
        <section class="flex flex-col sm:flex-row items-center sm:items-start gap-8 sm:gap-10 fade-1">

            <!-- Photo -->
            <div class="flex-shrink-0">
                <img src="assets/img/PHOTO_AGRE.png"
                    alt="Ange-Kevin AGRE"
                    class="w-32 h-32 sm:w-40 sm:h-40 rounded-2xl object-cover object-top photo-glow select-none"
                    onerror="this.style.display='none'">
            </div>

            <!-- Identité -->
            <div class="text-center sm:text-left space-y-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight leading-tight">
                        ANGE-KEVIN AGRE
                    </h1>
                    <p class="text-sm font-semibold text-sky-400 mt-1.5" id="txt-title">
                        Informaticien — Double compétence : Développement d'applications &amp; Sécurité réseaux
                    </p>
                </div>

                <!-- Compétences tags -->
                <div class="flex flex-wrap justify-center sm:justify-start gap-2 pt-1">
                    <span class="skill-tag">Full-Stack Dev</span>
                    <span class="skill-tag">Network Security</span>
                    <span class="skill-tag">Cisco IOS/IOS-XE</span>
                    <span class="skill-tag">Audit &amp; Hardening</span>
                    <span class="skill-tag">PHP / JS</span>
                </div>

                <!-- Contact -->
                <div class="flex flex-wrap justify-center sm:justify-start gap-x-5 gap-y-2 pt-1">
                    <a href="mailto:agrekevin09@gmail.com" class="contact-link">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 flex-shrink-0">
                            <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                            <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                        </svg>
                        agrekevin09@gmail.com
                    </a>
                    <a href="https://www.linkedin.com/in/ange-kevin-agre-a03b3a386"
                        target="_blank" rel="noopener noreferrer" class="contact-link">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 flex-shrink-0">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                        </svg>
                        <span id="txt-linkedin">Profil LinkedIn</span>
                    </a>
                </div>
            </div>
        </section>

        <div class="separator fade-2"></div>

        <!-- ── Bio technique ─────────────────────────────────────── -->
        <section class="neon-border bg-slate-900 rounded-xl p-6 sm:p-8 space-y-4 fade-2">
            <h2 class="text-xs font-bold text-sky-400 tracking-widest uppercase" id="txt-about-label">
                Profil
            </h2>
            <p class="text-slate-200 leading-relaxed text-sm sm:text-base" id="txt-bio">
                Expert technique double compétence. Je conçois et développe des architectures logicielles full-stack
                tout en assurant l'audit, le durcissement (hardening) et la sécurisation des infrastructures réseaux.
            </p>
        </section>

        <!-- ── Deux colonnes expertise ────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 fade-3">

            <!-- Dev -->
            <section class="spotlight-card bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-800 border border-sky-500/20 flex items-center justify-center flex-shrink-0">
                        <svg viewBox="0 0 20 20" fill="none" stroke="#38bdf8" stroke-width="1.5" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0019.5 18V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75V18a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-white text-sm" id="txt-dev-title">Développement Full-Stack</h3>
                </div>
                <ul class="space-y-1.5 text-xs text-slate-400">
                    <li id="txt-dev-1" class="flex items-start gap-2"><span class="text-sky-500 mt-0.5">—</span> Conception d'architectures logicielles robustes</li>
                    <li id="txt-dev-2" class="flex items-start gap-2"><span class="text-sky-500 mt-0.5">—</span> Développement backend PHP 8 & frontend JS/Tailwind</li>
                    <li id="txt-dev-3" class="flex items-start gap-2"><span class="text-sky-500 mt-0.5">—</span> Intégration d'API REST & moteurs d'intelligence artificielle</li>
                    <li id="txt-dev-4" class="flex items-start gap-2"><span class="text-sky-500 mt-0.5">—</span> Optimisation des performances & sécurité applicative</li>
                </ul>
            </section>

            <!-- Sécurité -->
            <section class="spotlight-card bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-800 border border-sky-500/20 flex items-center justify-center flex-shrink-0">
                        <svg viewBox="0 0 20 20" fill="none" stroke="#38bdf8" stroke-width="1.5" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75m-3-7l-7 3v5c0 4.418 3.134 7.674 7 8 3.866-.326 7-3.582 7-8V5.75l-7-3z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-white text-sm" id="txt-sec-title">Sécurité Réseaux</h3>
                </div>
                <ul class="space-y-1.5 text-xs text-slate-400">
                    <li id="txt-sec-1" class="flex items-start gap-2"><span class="text-sky-500 mt-0.5">—</span> Audit de configurations Cisco IOS/IOS-XE</li>
                    <li id="txt-sec-2" class="flex items-start gap-2"><span class="text-sky-500 mt-0.5">—</span> Hardening : ACL, SSH, SNMP, AAA, CDP</li>
                    <li id="txt-sec-3" class="flex items-start gap-2"><span class="text-sky-500 mt-0.5">—</span> Conformité CIS Benchmarks & NIST SP 800-115</li>
                    <li id="txt-sec-4" class="flex items-start gap-2"><span class="text-sky-500 mt-0.5">—</span> Détection de vulnérabilités & remédiation CLI</li>
                </ul>
            </section>
        </div>

        <div class="separator fade-4"></div>

        <!-- ── CTA retour ─────────────────────────────────────────── -->
        <div class="text-center fade-4">
            <a href="index.php"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-lg font-semibold text-sm
                  bg-sky-600 hover:bg-sky-500 text-white transition-colors duration-200">
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                <span id="txt-cta">Lancer un audit de configuration</span>
            </a>
        </div>

    </main>

    <footer class="border-t border-slate-800 mt-8 py-5">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
            <p class="text-xs text-slate-700">&copy; <?= date('Y') ?> Agre Agency — Net Auditor IA</p>
        </div>
    </footer>

    <script>
        'use strict';

        const texts = {
            fr: {
                navBack: 'Retour à l\'outil',
                title: 'Informaticien — Double compétence : Développement d\'applications & Sécurité réseaux',
                bio: 'Expert technique double compétence. Je conçois et développe des architectures logicielles full-stack tout en assurant l\'audit, le durcissement (hardening) et la sécurisation des infrastructures réseaux.',
                aboutLabel: 'Profil',
                linkedin: 'Profil LinkedIn',
                devTitle: 'Développement Full-Stack',
                dev1: '— Conception d\'architectures logicielles robustes',
                dev2: '— Développement backend PHP 8 & frontend JS/Tailwind',
                dev3: '— Intégration d\'API REST & moteurs d\'intelligence artificielle',
                dev4: '— Optimisation des performances & sécurité applicative',
                secTitle: 'Sécurité Réseaux',
                sec1: '— Audit de configurations Cisco IOS/IOS-XE',
                sec2: '— Hardening : ACL, SSH, SNMP, AAA, CDP',
                sec3: '— Conformité CIS Benchmarks & NIST SP 800-115',
                sec4: '— Détection de vulnérabilités & remédiation CLI',
                cta: 'Lancer un audit de configuration',
            },
            en: {
                navBack: 'Back to the tool',
                title: 'Computer Scientist — Dual competence: Application Development & Network Security',
                bio: 'Dual-skilled technical expert. I design and develop full-stack software architectures while ensuring network infrastructure auditing, hardening, and security.',
                aboutLabel: 'Profile',
                linkedin: 'LinkedIn Profile',
                devTitle: 'Full-Stack Development',
                dev1: '— Design of robust software architectures',
                dev2: '— PHP 8 backend & JS/Tailwind frontend development',
                dev3: '— REST API & AI engine integration',
                dev4: '— Performance optimization & application security',
                secTitle: 'Network Security',
                sec1: '— Cisco IOS/IOS-XE configuration auditing',
                sec2: '— Hardening: ACL, SSH, SNMP, AAA, CDP',
                sec3: '— CIS Benchmarks & NIST SP 800-115 compliance',
                sec4: '— Vulnerability detection & CLI remediation',
                cta: 'Launch a configuration audit',
            },
        };

        let currentLang = localStorage.getItem('nai_lang') || 'fr';

        function applyLang(lang) {
            const t = texts[lang] || texts.fr;
            currentLang = lang;
            localStorage.setItem('nai_lang', lang);
            document.documentElement.lang = lang;

            document.getElementById('nav-back').textContent = t.navBack;
            document.getElementById('txt-title').textContent = t.title;
            document.getElementById('txt-bio').textContent = t.bio;
            document.getElementById('txt-about-label').textContent = t.aboutLabel;
            document.getElementById('txt-linkedin').textContent = t.linkedin;
            document.getElementById('txt-dev-title').textContent = t.devTitle;
            document.getElementById('txt-dev-1').innerHTML = '<span class="text-sky-500 mt-0.5">—</span> ' + t.dev1.replace(/^— /, '');
            document.getElementById('txt-dev-2').innerHTML = '<span class="text-sky-500 mt-0.5">—</span> ' + t.dev2.replace(/^— /, '');
            document.getElementById('txt-dev-3').innerHTML = '<span class="text-sky-500 mt-0.5">—</span> ' + t.dev3.replace(/^— /, '');
            document.getElementById('txt-dev-4').innerHTML = '<span class="text-sky-500 mt-0.5">—</span> ' + t.dev4.replace(/^— /, '');
            document.getElementById('txt-sec-title').textContent = t.secTitle;
            document.getElementById('txt-sec-1').innerHTML = '<span class="text-sky-500 mt-0.5">—</span> ' + t.sec1.replace(/^— /, '');
            document.getElementById('txt-sec-2').innerHTML = '<span class="text-sky-500 mt-0.5">—</span> ' + t.sec2.replace(/^— /, '');
            document.getElementById('txt-sec-3').innerHTML = '<span class="text-sky-500 mt-0.5">—</span> ' + t.sec3.replace(/^— /, '');
            document.getElementById('txt-sec-4').innerHTML = '<span class="text-sky-500 mt-0.5">—</span> ' + t.sec4.replace(/^— /, '');
            document.getElementById('txt-cta').textContent = t.cta;

            document.getElementById('btn-fr').classList.toggle('text-sky-400', lang === 'fr');
            document.getElementById('btn-fr').classList.toggle('text-slate-400', lang !== 'fr');
            document.getElementById('btn-en').classList.toggle('text-sky-400', lang === 'en');
            document.getElementById('btn-en').classList.toggle('text-slate-400', lang !== 'en');
        }

        function setLang(lang) {
            applyLang(lang);
        }

        applyLang(currentLang);

        // Spotlight sur les cartes d'expertise
        document.querySelectorAll('.spotlight-card').forEach(card => {
            card.addEventListener('mousemove', e => {
                const r = card.getBoundingClientRect();
                card.style.setProperty('--mouse-x', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
                card.style.setProperty('--mouse-y', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
            });
        });
    </script>
</body>

</html>