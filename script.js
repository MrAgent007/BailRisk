console.log("Script.js loaded at start");

// Utility Functions
function showSection(sectionId) {
    console.log(`Showing section: ${sectionId}`);
    const sections = document.querySelectorAll('.dashboard-section');
    const navButtons = document.querySelectorAll('.nav-btn');
    sections.forEach(section => section.classList.remove('active'));
    navButtons.forEach(btn => btn.classList.remove('active'));
    const targetSection = document.getElementById(sectionId);
    const activeBtn = document.querySelector(`.nav-btn[data-section="${sectionId}"]`);
    if (targetSection) targetSection.classList.add('active');
    if (activeBtn) activeBtn.classList.add('active');
}

function logAction(action) {
    console.log(`Action logged: ${action}`);
}

// Global Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded");

    // Theme Toggle (Global)
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        let isDarkMode = localStorage.getItem('darkMode') === 'true';
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        themeToggle.addEventListener('click', () => {
            isDarkMode = !isDarkMode;
            document.body.classList.toggle('dark-mode', isDarkMode);
            localStorage.setItem('darkMode', isDarkMode);
            themeToggle.innerHTML = isDarkMode ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
    }

    // Navigation (Only for Agent Dashboard)
    if (window.location.pathname.endsWith('/agent-dashboard.html')) {
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                showSection(btn.dataset.section);
            });
        });
    }

    // Logout (Shared across dashboards)
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('/php/logout.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                await response.json();
                window.location.href = '/login.html';
            } catch (error) {
                console.error("Logout error:", error);
                alert('Logout failed: ' + error.message);
            }
        });
    }

    // Agent Dashboard Logic
    if (window.location.pathname.endsWith('/agent-dashboard.html')) {
        console.log("Loaded agent dashboard");
        loadAgentDashboard();
    }
    // Defendant Dashboard uses inline JS, so no logic here
});

// Agent Dashboard Functions
async function loadAgentDashboard() {
    try {
        const response = await fetch('/php/agent_dashboard_data.php', { credentials: 'same-origin' });
        const data = await response.json();
        if (!data.success) {
            alert(data.message);
            window.location.href = '/login.html';
            return;
        }

        document.getElementById('userName').textContent = data.data.agent_name;
        document.getElementById('profilePic').src = data.data.agent_profile_pic || '/images/default-profile.jpg';
        document.getElementById('adminDashboardLink').style.display = data.data.is_admin ? 'block' : 'none';

        const now = new Date();
        const defendants = data.data.defendants || [];
        document.getElementById('totalDefendants').textContent = defendants.length;
        document.getElementById('pendingCheckins').textContent = defendants.filter(d => d.next_due_date && new Date(d.next_due_date) <= now && !d.checkins.some(c => new Date(c.date) >= new Date(d.next_due_date))).length;
        document.getElementById('missedCheckins').textContent = defendants.filter(d => d.next_due_date && new Date(d.next_due_date) < now && !d.checkins.some(c => new Date(c.date) >= new Date(d.next_due_date))).length;
        document.getElementById('lateCheckins').textContent = defendants.filter(d => d.checkins.some(c => d.next_due_date && new Date(c.date) > new Date(d.next_due_date))).length;
        document.getElementById('missedPayments').textContent = defendants.filter(d => d.next_due_date && new Date(d.next_due_date) < now && d.bail_due > 0).length;
        document.getElementById('totalPayments').textContent = `$${defendants.reduce((sum, d) => sum + (d.payments.reduce((pSum, p) => pSum + parseFloat(p.amount), 0)), 0).toFixed(2)}`;
        document.getElementById('overduePayments').textContent = `$${defendants.reduce((sum, d) => sum + (d.next_due_date && new Date(d.next_due_date) < now ? d.bail_due : 0), 0).toFixed(2)}`;

        let filteredDefendants = defendants;
        function renderDefendants(defendantsList) {
            const defendantList = document.getElementById('defendantList');
            defendantList.innerHTML = defendantsList.length > 0 ? defendantsList.map(d => `
                <div>
                    <p><a href="/defendant-profile.html?id=${d.user_id}">${d.name}</a></p>
                    <p style="color: ${d.verified ? 'green' : 'red'}">${d.verified ? 'Verified' : 'Unverified'}</p>
                    <p>${d.case_number || 'N/A'}</p>
                    <p>$${d.bail_due ? parseFloat(d.bail_due).toFixed(2) : '0.00'}</p>
                    <p>${d.next_due_date || 'N/A'}</p>
                </div>
            `).join('') : 'No defendants found.';
        }
        renderDefendants(filteredDefendants);

        document.getElementById('defendantSearch').addEventListener('input', filterDefendants);
        document.getElementById('statusFilter').addEventListener('change', filterDefendants);

        function filterDefendants() {
            const search = document.getElementById('defendantSearch').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            filteredDefendants = defendants.filter(d => {
                const matchesSearch = d.name.toLowerCase().includes(search) || (d.case_number && d.case_number.toLowerCase().includes(search));
                if (status === 'all') return matchesSearch;
                if (status === 'verified') return matchesSearch && d.verified;
                if (status === 'unverified') return matchesSearch && !d.verified;
                if (status === 'missed-checkins') return matchesSearch && d.next_due_date && new Date(d.next_due_date) < now && !d.checkins.some(c => new Date(c.date) >= new Date(d.next_due_date));
                if (status === 'late-checkins') return matchesSearch && d.checkins.some(c => d.next_due_date && new Date(c.date) > new Date(d.next_due_date));
                if (status === 'missed-payments') return matchesSearch && d.next_due_date && new Date(d.next_due_date) < now && d.bail_due > 0;
                return false;
            });
            renderDefendants(filteredDefendants);
        }

        document.getElementById('addDefendantForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('/php/add_defendant.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    e.target.reset();
                    loadAgentDashboard();
                }
            } catch (error) {
                console.error("Add defendant error:", error);
                alert('Failed to add defendant: ' + error.message);
            }
        });

        const travelResponse = await fetch(`/php/get_travel_requests.php?agent_id=${data.data.agent_id}`, { credentials: 'same-origin' });
        const travelResult = await travelResponse.json();
        if (travelResult.success) {
            document.getElementById('travelRequestList').innerHTML = travelResult.data.length > 0 ? travelResult.data.map(r => `
                <div>
                    <p><a href="/defendant-profile.html?id=${r.defendant_id}">${r.defendant_id}</a></p>
                    <p>${r.request_date}</p>
                    <p>${r.destination}</p>
                    <p>${r.start_date} - ${r.end_date}</p>
                    <p>${r.status}</p>
                    <p>
                        <button class="btn btn-primary" onclick="updateTravelStatus(${r.id}, 'Approved')">Approve</button>
                        <button class="btn btn-secondary" onclick="updateTravelStatus(${r.id}, 'Denied')">Deny</button>
                        <button class="btn btn-warning" onclick="updateTravelStatus(${r.id}, 'Contact Agent')">Contact</button>
                    </p>
                </div>
            `).join('') : 'No travel requests.';
        }

        const notifResponse = await fetch('/php/get_notifications.php', { credentials: 'same-origin' });
        const notifResult = await notifResponse.json();
        if (notifResult.success) {
            const bubble = document.getElementById('notificationBubble');
            bubble.textContent = notifResult.data.length;
            bubble.style.display = notifResult.data.length > 0 ? 'inline-block' : 'none';
        }

        // Admin Tools
        const toggleReminderBtn = document.getElementById('toggleReminderBtn');
        if (toggleReminderBtn) {
            toggleReminderBtn.addEventListener('click', async () => {
                try {
                    const response = await fetch('/php/toggle_reminders.php', {
                        method: 'POST',
                        credentials: 'same-origin'
                    });
                    const result = await response.json();
                    if (result.success) {
                        document.getElementById('autoReminderStatus').textContent = result.status ? 'On' : 'Off';
                        alert(result.message);
                    }
                } catch (error) {
                    console.error("Toggle reminders error:", error);
                    alert('Failed to toggle reminders: ' + error.message);
                }
            });
        }

        const sendSystemAlertBtn = document.getElementById('sendSystemAlertBtn');
        if (sendSystemAlertBtn) {
            sendSystemAlertBtn.addEventListener('click', async () => {
                const message = prompt('Enter system alert message:');
                if (message) {
                    try {
                        const response = await fetch('/php/send_custom_notification.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `message=${encodeURIComponent(message)}`,
                            credentials: 'same-origin'
                        });
                        const result = await response.json();
                        alert(result.message);
                    } catch (error) {
                        console.error("Send alert error:", error);
                        alert('Failed to send alert: ' + error.message);
                    }
                }
            });
        }

        showSection('overview');
    } catch (error) {
        console.error("Dashboard load error:", error);
        alert('Error loading dashboard: ' + error.message);
        window.location.href = '/login.html';
    }
}

// Travel Status Update (Agent Dashboard)
window.updateTravelStatus = async function(requestId, status) {
    try {
        const response = await fetch('/php/update_travel_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId, status }),
            credentials: 'same-origin'
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) loadAgentDashboard();
    } catch (error) {
        console.error("Travel status update error:", error);
        alert('Failed to update travel status: ' + error.message);
    }
};