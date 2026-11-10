const password = document.querySelector('#password');
const toggle = document.querySelector('#password-toggle');

if (password && toggle) {
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const reveal = password.type === 'password';
        password.type = reveal ? 'text' : 'password';
        toggle.textContent = reveal ? 'Hide' : 'Show';
    });
}

const printSummary = document.querySelector('#print-summary');
if (printSummary) {
    printSummary.hidden = false;
    printSummary.addEventListener('click', () => window.print());
}

const portalMenu = document.querySelector('.portal-menu-toggle');

if (portalMenu) {
    portalMenu.hidden = false;
    portalMenu.setAttribute('aria-expanded', 'false');

    portalMenu.addEventListener('click', () => {
        const expanded = portalMenu.getAttribute('aria-expanded') === 'true';
        portalMenu.setAttribute('aria-expanded', String(!expanded));
    });

    portalMenu.closest('.portal-sidebar').addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && portalMenu.getAttribute('aria-expanded') === 'true'
            && portalMenu.getClientRects().length) {
            portalMenu.setAttribute('aria-expanded', 'false');
            portalMenu.focus();
        }
    });
}
