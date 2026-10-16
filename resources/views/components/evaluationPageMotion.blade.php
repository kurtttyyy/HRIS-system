<style>
    @view-transition { navigation: auto; }
    .evaluation-page-shell { perspective: 1400px; }
    .evaluation-tabs { view-transition-name: evaluation-tabs; perspective: 800px; }
    .evaluation-tabs a {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        transform-style: preserve-3d;
        transition: transform 220ms ease, box-shadow 220ms ease, background-color 220ms ease;
    }
    .evaluation-tabs a:focus-visible { outline: 3px solid #34d399; outline-offset: 3px; }
    .evaluation-tabs a[aria-current="page"] { box-shadow: 0 5px 12px rgba(5, 150, 105, .18); }
    @media (hover: hover) and (pointer: fine) {
        .evaluation-tabs a:hover {
            transform: translateY(-3px) rotateX(9deg) rotateY(-4deg);
            box-shadow: 0 8px 16px rgba(15, 23, 42, .12);
        }
        .evaluation-tabs a:active { transform: translateY(0) scale(.98); }
    }
    #evaluation-template-region, #evaluation-reviews-region {
        view-transition-name: evaluation-content;
        transform-origin: 50% 15%;
        animation: evaluation-arrive 440ms cubic-bezier(.22, 1, .36, 1) both;
    }
    @keyframes evaluation-arrive {
        from { opacity: 0; transform: translateY(16px) rotateX(5deg) scale(.985); }
        to { opacity: 1; transform: translateY(0) rotateX(0) scale(1); }
    }
    @keyframes evaluation-depart {
        to { opacity: 0; transform: perspective(1400px) translateY(-8px) rotateX(-4deg) scale(.985); }
    }
    ::view-transition-old(evaluation-content) { animation: evaluation-depart 180ms ease-in both; }
    ::view-transition-new(evaluation-content) { animation: evaluation-arrive 420ms cubic-bezier(.22, 1, .36, 1) both; }
    ::view-transition-group(evaluation-tabs) { animation-duration: 300ms; }
    html.evaluation-native-transition #evaluation-template-region,
    html.evaluation-native-transition #evaluation-reviews-region { animation: none; }
    @media (prefers-reduced-motion: reduce) {
        .evaluation-tabs a { transition: none; transform: none !important; }
        #evaluation-template-region, #evaluation-reviews-region { animation: none; }
        ::view-transition-group(*), ::view-transition-old(*), ::view-transition-new(*) { animation: none !important; }
    }
</style>
<script>
    window.addEventListener('pagereveal', (event) => {
        if (event.viewTransition) document.documentElement.classList.add('evaluation-native-transition');
    });
</script>
