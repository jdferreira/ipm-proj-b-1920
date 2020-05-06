let data;

document.addEventListener('DOMContentLoaded', () => {
    setupEvents();
});

function setupEvents() {
    document.querySelector('#login button').addEventListener('click', login);
    document.querySelector('#main-data').addEventListener('click', dataClick);
}

async function login() {
    let code = document.querySelector('#login input').value;

    const response = await fetch(
        'http://areasgrupo.alunos.di.fc.ul.pt/~ipm000/projb/dashboard.php',
        {
            method: 'POST',
            body: JSON.stringify({
                code: code,
            })
        }
    );

    const result = await response.json();

    if (result.success) {
        data = result.data;
        showData();
        hideError();
    } else {
        data = undefined;
        showError(result.error);
    }
}

function showError(error) {
    document.getElementById('error-code').innerText = error;
    document.getElementById('error-code').style.display = 'block';
}

function hideError() {
    document.getElementById('error-code').style.display = 'none';
}

function dataClick(ev) {
    let row = ev.target.closest('[data-details-id]');

    if (row === null) {
        return;
    }

    let id = 'details-' + row.dataset.detailsId;

    let details = document.getElementById(id);

    let hidden = getComputedStyle(details).display === 'none';

    document.querySelectorAll('.details').forEach(el => el.style.display = 'none');
    document.querySelectorAll('#main-data > tbody > tr').forEach(el => el.classList.remove('active'));

    if (hidden) {
        details.style.display = 'table-row';
        row.classList.add('active');
        details.classList.add('active');
    } else {
        details.style.display = 'none';
    }
}

function showData() {
    document.querySelectorAll('#main-data > tbody > tr').forEach(
        el => el.remove()
    );

    for (let id of Object.keys(data)) {
        addRow(id);
    }
}

function addRow(id) {
    let instance = data[id];

    let template = document.getElementById('main-data-row-template');
    let clone = template.content.cloneNode(true);

    clone.querySelectorAll('[data-grab-from]').forEach(el => {
        let value = instance[el.dataset.grabFrom];

        if (el.dataset.grabFrom === 'sequence') {
            el.textContent = formatSequence(value);
        } else if (el.dataset.grabFrom === 'time') {
            el.textContent = formatDate(value);
        } else {
            el.textContent = value;
        }
    });

    clone.querySelector('.main-row').dataset.detailsId = id;
    clone.querySelector('.details').id = 'details-' + id;

    let tbody = clone.querySelector('.interactions tbody')
    for (let interaction of instance.interactions) {
        addDetailsRow(interaction, tbody);
    }

    document.querySelector('#main-data > tbody').appendChild(clone);
}

function addDetailsRow(interaction, tbody) {
    let template = document.getElementById('details-row-template');
    let clone = template.content.cloneNode(true);

    clone.querySelectorAll('[data-grab-from]').forEach(el => {
        let value = interaction[el.dataset.grabFrom];

        if (el.dataset.grabFrom === 'x' || el.dataset.grabFrom === 'y') {
            el.textContent = formatCoordinate(value);
        } else if (el.dataset.grabFrom === 'elapsed') {
            el.textContent = value.toFixed(1) + ' seconds';
        } else {
            el.textContent = value;
        }
    });

    tbody.appendChild(clone);
}

function formatCoordinate(value) {
    return value.toFixed(0);
}

function formatSequence(sequence) {
    return sequence.map(pair => '(' + pair.join(', ') + ')').join(' ');
}

function formatDate(value) {
    let date = new Date(value * 1000); // PHP gives us the number of seconds since epoch, JS wants milliseconds

    let y = new String(date.getFullYear()).padStart(2, '0');
    let m = new String(date.getMonth() + 1).padStart(2, '0');
    let d = new String(date.getDate()).padStart(2, '0');
    let h = new String(date.getHours()).padStart(2, '0');
    let i = new String(date.getMinutes()).padStart(2, '0');
    let s = new String(date.getSeconds()).padStart(2, '0');

    return `${y}/${m}/${d} ${h}:${i}:${s}`;
}
