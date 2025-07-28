const statusOptions = window.statusOptions || "";

// Sends AJAX requests to backend
function sendForm(data, onSuccess) {
    fetch("", { method: "POST", body: data })
        .then(res => res.json())
        .then(json => { if (json.success) onSuccess(json); });
}

// Handles client-side behavior
function attachHandlers() {
    document.querySelectorAll(".deleteBtn").forEach(btn => {
        btn.onclick = () => {
            const row = btn.closest("tr");
            const data = new FormData();
            data.append("action", "delete");
            data.append("id", row.dataset.id);
            sendForm(data, () => row.remove());
        };
    });

    document.querySelectorAll(".statusSelect").forEach(select => {
        select.onchange = () => {
            const row = select.closest("tr");
            const data = new FormData();
            data.append("action", "update");
            data.append("id", row.dataset.id);
            data.append("status", select.value);
            sendForm(data, () => {
                row.cells[1].textContent = select.value;
            });
        };
    });

    document.querySelectorAll(".nameInput").forEach(input => {
        input.onblur = () => {
            const row = input.closest("tr");
            const data = new FormData();
            data.append("action", "rename");
            data.append("id", row.dataset.id);
            data.append("name", input.value.trim());
            sendForm(data, () => {});
        };
    });
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("addTaskForm").onsubmit = function (e) {
        e.preventDefault();
        const name = this.name.value.trim();
        if (!name) return;

        const data = new FormData();
        data.append("action", "add");
        data.append("name", name);

        sendForm(data, json => {
            const task = json.task;
            const row = document.createElement("tr");
            row.dataset.id = task.id;
            row.innerHTML = `
                <td><input class="nameInput" type="text" value="${task.name}"></td>
                <td>${task.status}</td>
                <td>${task.creationDate}</td>
                <td><select class="statusSelect">${statusOptions.replace(
                `value="${task.status}"`,
                `value="${task.status}" selected`
            )}</select></td>
                <td><button class="deleteBtn">Delete</button></td>
            `;
            document.querySelector("#taskTable tbody").appendChild(row);
            attachHandlers();
            this.reset();
        });
    };

    document.getElementById("statusFilter").onchange = function () {
        const data = new FormData();
        data.append("action", "filter");
        data.append("status", this.value);
        sendForm(data, json => {
            document.querySelector("#taskTable tbody").innerHTML = json.html;
            attachHandlers();
        });
    };

    attachHandlers();
});
