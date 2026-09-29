// The frontend only knows about the API contract (/api/...), never about
// PHP or Python. That is what lets us swap backends without touching this file.
const API_BASE = '/api';

const form = document.getElementById('registration-form');
const submitBtn = document.getElementById('submit-btn');
const formMessage = document.getElementById('form-message');
const studentList = document.getElementById('student-list');
const apiStatus = document.getElementById('api-status');

async function api(path, options = {}) {
    const response = await fetch(API_BASE + path, {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        ...options,
    });
    const body = await response.json().catch(() => ({}));
    return { ok: response.ok, status: response.status, body };
}

function showMessage(text, kind) {
    formMessage.textContent = text;
    formMessage.className = `form-message ${kind}`;
}

function clearFieldErrors() {
    form.querySelectorAll('[data-error-for]').forEach((el) => { el.textContent = ''; });
    form.querySelectorAll('.invalid').forEach((el) => el.classList.remove('invalid'));
}

function showFieldErrors(errors) {
    Object.entries(errors).forEach(([field, message]) => {
        const slot = form.querySelector(`[data-error-for="${field}"]`);
        if (slot) slot.textContent = message;
        form.elements[field]?.classList.add('invalid');
    });
}

async function loadHealth() {
    try {
        const { ok, body } = await api('/health');
        apiStatus.textContent = ok ? `API ${body.version?.slice(0, 7) ?? ''} online` : 'API error';
        apiStatus.className = `badge ${ok ? 'ok' : 'down'}`;
    } catch {
        apiStatus.textContent = 'API offline';
        apiStatus.className = 'badge down';
    }
}

async function loadStudents() {
    try {
        const { ok, body } = await api('/students');
        studentList.replaceChildren();

        if (!ok) {
            studentList.append(listItem(body.error ?? 'Could not load students.'));
            return;
        }
        if (body.students.length === 0) {
            studentList.append(listItem('No students registered yet. Be the first!'));
            return;
        }
        body.students.forEach((s) => {
            const li = document.createElement('li');
            const name = document.createElement('span');
            const course = document.createElement('span');
            name.textContent = s.full_name;          // textContent = no XSS
            course.textContent = s.course;
            course.className = 'muted';
            li.append(name, course);
            studentList.append(li);
        });
    } catch {
        studentList.replaceChildren(listItem('Could not reach the API.'));
    }
}

function listItem(text) {
    const li = document.createElement('li');
    li.className = 'muted';
    li.textContent = text;
    return li;
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearFieldErrors();
    showMessage('', '');
    submitBtn.disabled = true;

    const payload = Object.fromEntries(new FormData(form).entries());

    try {
        const { status, body } = await api('/students', { method: 'POST', body: JSON.stringify(payload) });

        if (status === 201) {
            form.reset();
            showMessage(body.message, 'success');
            loadStudents();
        } else {
            if (body.errors) showFieldErrors(body.errors);
            showMessage(body.error ?? `Request failed (HTTP ${status})`, 'failure');
        }
    } catch {
        showMessage('Network error - is the API running?', 'failure');
    } finally {
        submitBtn.disabled = false;
    }
});

loadHealth();
loadStudents();
