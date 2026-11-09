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
