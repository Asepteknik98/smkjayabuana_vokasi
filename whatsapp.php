
<?php
// Tombol WhatsApp untuk semua halaman
$nomorWA = '6283871714458';

$pesanWA = 'Halo Admin Pariwisata Banten, saya ingin bertanya tentang wisata di Banten.';

$linkWA = 'https://wa.me/' . $nomorWA
        . '?text=' . rawurlencode($pesanWA);
?>

<style>
/* TOMBOL WHATSAPP MENGAMBANG */

.wa-floating {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 9999;

    display: flex;
    align-items: center;
    justify-content: center;

    width: 55px;
    height: 55px;

    background: #25d366;
    color: white;

    border-radius: 50%;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);

    text-decoration: none;
    transition: transform 0.2s, background 0.2s;
}

.wa-floating:hover {
    background: #1ebe5d;
    transform: scale(1.08);
}

.wa-floating svg {
    width: 29px;
    height: 29px;
    fill: currentColor;
}

/* TAMPILAN HP */

@media (max-width: 700px) {
    .wa-floating {
        right: 12px;
        bottom: 12px;
        width: 48px;
        height: 48px;
    }

    .wa-floating svg {
        width: 26px;
        height: 26px;
    }
}
</style>

<a
    href="<?= htmlspecialchars($linkWA, ENT_QUOTES, 'UTF-8') ?>"
    class="wa-floating"
    target="_blank"
    rel="noopener noreferrer"
    title="Chat Admin via WhatsApp"
    aria-label="Chat Admin via WhatsApp"
>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        aria-hidden="true"
    >
        <path d="M16 .8A15.1 15.1 0 0 0 3 23.6L.9 31l7.6-2A15.2 15.2 0 1 0 16 .8zm0 27.6a12.3 12.3 0 0 1-6.3-1.7l-.5-.3-4.5 1.2 1.2-4.4-.3-.5A12.4 12.4 0 1 1 16 28.4zm6.8-9.3c-.4-.2-2.2-1.1-2.5-1.2-.3-.1-.6-.2-.8.2-.2.4-1 1.2-1.2 1.5-.2.2-.4.3-.8.1-.4-.2-1.6-.6-3-1.9-1.1-1-1.9-2.2-2.1-2.6-.2-.4 0-.6.2-.8l.6-.7.4-.6c.1-.2 0-.5 0-.7l-1.2-2.8c-.3-.7-.6-.6-.8-.6h-.7c-.3 0-.7.1-1 .5-.3.4-1.3 1.3-1.3 3.1s1.3 3.5 1.5 3.8c.2.2 2.6 4 6.3 5.5.9.4 1.6.6 2.1.8.9.3 1.7.3 2.3.2.7-.1 2.2-.9 2.5-1.8.3-.9.3-1.7.2-1.8-.1-.2-.3-.3-.7-.5z"/>
    </svg>
</a>
