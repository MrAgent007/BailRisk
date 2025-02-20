// Simulated data storage
let currentUser = null;
let agents = [
    { id: "AGT12345", name: "Agent Smith", email: "smith@agency.com", license: "FL12345", docs: "Uploaded", subscription: "Active", isAdmin: true }
];
let pendingAgents = [];
let defendants = [
    { id: "DEF12345", name: "John Doe", agentId: null, checkins: [], missed: [], riskScore: 0, mugshot: "https://images.unsplash.com/photo-1511367461989-f85a21fda167?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" }
];
let checkins = [];
let notifications = [];
let reminders = [];
let messages = [];
let autoReminders = false;

// Utility functions
function logAction(action) {
    notifications.push({ message: action, date: new Date().toLocaleString(), type: 'system' });
    updateSystemLogs();
}

function addNotification(message, userType) {
    notifications.push({ message, date: new Date().toLocaleString(), type: userType });
    updateNotifications(userType);
}

function updateNotifications(userType) {
    const list = document.getElementById('notificationList');
    if (list) {
        list.innerHTML = notifications
            .filter(n => n.type === userType || n.type === 'system')
            .map(n => `<p><i class="fas fa-bell"></i> ${n.message} <small>${n.date}</small></p>`).join('');
    }
}

function updateSystemLogs() {
    const logs = document.getElementById('systemLogs');
    if (logs) {
        logs.innerHTML = notifications
            .filter(n => n.type === 'system')
            .map(n => `<p>${n.date}: ${n.message}</p>`).join('');
    }
}

// Defendant Login
document.getElementById('defendantLoginForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const id = document.getElementById('defendantId').value;
    currentUser = defendants.find(d => d.id === id) || { id, name: id, agentId: null, checkins: [], missed: [], riskScore: 0, mugshot: "https://images.unsplash.com/photo-1511367461989-f85a21fda167?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" };
    if (!defendants.some(d => d.id === id)) defendants.push(currentUser);
    logAction(`${currentUser.name} logged in as defendant`);
    window.location.href = 'defendant-dashboard.html';
});

// Agent Login
document.getElementById('agentLoginForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const id = document.getElementById('agentId').value;
    currentUser = agents.find(a => a.id === id);
    if (!currentUser) {
        alert('Agent not approved yet.');
        return;
    }
    logAction(`${currentUser.name} logged in`);
    window.location.href = currentUser.isAdmin ? 'admin-dashboard.html' : 'agent-dashboard.html';
});

// Agent Sign-Up
document.getElementById('agentSignupForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const agent = {
        id: `AGT${Math.floor(10000 + Math.random() * 90000)}`,
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        license: document.getElementById('license').value,
        docs: document.getElementById('docs').files.length ? 'Uploaded' : 'None',
        password: document.getElementById('password').value,
        subscription: 'Pending',
        isAdmin: false
    };
    pendingAgents.push(agent);
    logAction(`${agent.name} submitted agent registration`);
    alert('Registration submitted for approval.');
    window.location.href = 'index.html';
});

// Agent Dashboard
if (window.location.pathname.endsWith('agent-dashboard.html')) {
    document.getElementById('agentName').textContent = currentUser?.name || 'Agent';
    document.getElementById('subscriptionStatus').textContent = currentUser?.subscription || 'Pending';

    const defendantList = document.getElementById('defendantList');
    defendantList.innerHTML = defendants
        .filter(d => d.agentId === currentUser.id)
        .map(d => `
            <div class="defendant-details">
                <p><strong>${d.name}</strong> (ID: ${d.id})</p>
                <p>Last Check-In: ${d.checkins[d.checkins.length - 1]?.date || 'None'}</p>
                <p>Risk Score: ${d.riskScore}</p>
                <p>Contact: ${d.checkins[d.checkins.length - 1]?.contact || 'Unknown'}</p>
                <p>Residence: ${d.checkins[d.checkins.length - 1]?.residence || 'Unknown'}</p>
                <p>Employment: ${d.checkins[d.checkins.length - 1]?.employment || 'Unknown'}</p>
                <p>Status: ${d.checkins[d.checkins.length - 1]?.status || 'No updates'}</p>
                <img src="${d.mugshot}" alt="Mugshot">
            </div>`).join('');

    const checkinList = document.getElementById('checkinList');
    checkinList.innerHTML = checkins
        .filter(c => defendants.find(d => d.id === c.defendantId && d.agentId === currentUser.id))
        .map(c => `<p>${defendants.find(d => d.id === c.defendantId).name}: ${c.date} - ${c.location}</p>`).join('');

    const missedList = document.getElementById('missedList');
    const now = Date.now();
    defendants.forEach(d => {
        if (d.agentId === currentUser.id && (!d.checkins.length || new Date(d.checkins[d.checkins.length - 1].date) < new Date(now - 7 * 24 * 60 * 60 * 1000))) {
            d.missed.push({ date: new Date().toLocaleDateString() });
        }
    });
    missedList.innerHTML = defendants
        .filter(d => d.agentId === currentUser.id && d.missed.length)
        .map(d => `<p>${d.name} - Missed: ${d.missed[d.missed.length - 1].date}</p>`).join('');

    const messageList = document.getElementById('messageList');
    messageList.innerHTML = messages
        .filter(m => m.from === currentUser.name)
        .map(m => `<p><strong>${m.from}</strong>: ${m.content} <small>${m.date}</small></p>`).join('');

    updateNotifications('agent');

    const agentChart = document.getElementById('agentAnalyticsChart').getContext('2d');
    new Chart(agentChart, {
        type: 'bar',
        data: {
            labels: ['Defendants', 'Check-Ins', 'Missed', 'Messages'],
            datasets: [{
                label: 'Agent Stats',
                data: [
                    defendants.filter(d => d.agentId === currentUser.id).length,
                    checkins.filter(c => defendants.find(d => d.id === c.defendantId && d.agentId === currentUser.id)).length,
                    defendants.filter(d => d.agentId === currentUser.id && d.missed.length).length,
                    messages.filter(m => m.from === currentUser.name).length
                ],
                backgroundColor: ['#fbbf24', '#38a169', '#e53e3e', '#3182ce']
            }]
        },
        options: { responsive: true }
    });
}

// Admin Dashboard
if (window.location.pathname.endsWith('admin-dashboard.html')) {
    if (!currentUser || !currentUser.isAdmin) {
        window.location.href = 'index.html'; // Redirect if not admin
    }

    document.getElementById('adminName').textContent = currentUser?.name || 'Admin';

    const pendingAgentsDiv = document.getElementById('pendingAgents');
    pendingAgentsDiv.innerHTML = pendingAgents.map((a, index) => `
        <p>${a.name} (${a.email}, License: ${a.license}) 
        - <button onclick="approveAgent(${index})"><i class="fas fa-check"></i> Approve</button> 
        - <button onclick="rejectAgent(${index})"><i class="fas fa-times"></i> Reject</button></p>`).join('');

    const agentSelect = document.getElementById('agentId');
    agentSelect.innerHTML = '<option value="">Select Agent</option>' + agents.map(a => `<option value="${a.id}">${a.name}</option>`).join('');

    const riskChart = document.getElementById('riskAnalyticsChart').getContext('2d');
    new Chart(riskChart, {
        type: 'pie',
        data: {
            labels: defendants.map(d => d.name),
            datasets: [{
                label: 'Risk Scores',
                data: defendants.map(d => d.riskScore),
                backgroundColor: defendants.map(() => `#${Math.floor(Math.random()*16777215).toString(16)}`)
            }]
        },
        options: { responsive: true }
    });

    document.getElementById('agentPerformance').innerHTML = agents.map(a => `
        <p>${a.name}: Defendants: ${defendants.filter(d => d.agentId === a.id).length}, Check-Ins: ${checkins.filter(c => defendants.find(d => d.id === c.defendantId && d.agentId === a.id)).length}</p>`).join('');

    updateSystemLogs();
    document.getElementById('autoReminderStatus').textContent = autoReminders ? 'On' : 'Off';

    document.getElementById('assignForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const defendantId = document.getElementById('defendantId').value;
        const agentId = document.getElementById('agentId').value;
        const defendant = defendants.find(d => d.id === defendantId);
        if (defendant) {
            defendant.agentId = agentId;
            logAction(`Assigned ${defendant.name} to ${agents.find(a => a.id === agentId).name}`);
            addNotification(`${defendant.name} assigned to you`, 'agent');
        } else {
            alert('Defendant not found.');
        }
        e.target.reset();
    });
}

function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => section.style.display = 'none');
    document.getElementById(sectionId).style.display = 'block';
}

function calculateRiskScores() {
    defendants
        .filter(d => d.agentId === currentUser.id)
        .forEach(d => {
            d.riskScore = d.missed.length * 20 + (d.checkins.length ? 0 : 50);
        });
    updateAgentUI();
    addNotification(`Risk scores updated`, 'agent');
}

function sendReminderAll() {
    defendants
        .filter(d => d.agentId === currentUser.id && d.missed.length)
        .forEach(d => {
            reminders.push({ defendantId: d.id, message: `Reminder: Check in required by ${new Date(Date.now() + 24 * 60 * 60 * 1000).toLocaleDateString()}`, date: new Date().toLocaleString() });
            addNotification(`Reminder sent to ${d.name}`, 'agent');
            addNotification(`Agent ${currentUser.name} requests your check-in by tomorrow`, 'defendant');
        });
    logAction(`${currentUser.name} sent reminders to missed defendants`);
}

function sendMessage() {
    const content = document.getElementById('messageInput').value;
    if (!content) return;
    const msg = { from: currentUser.name, content, date: new Date().toLocaleString() };
    messages.push(msg);
    document.getElementById('messageList').innerHTML += `<p><strong>${msg.from}</strong>: ${msg.content} <small>${msg.date}</small></p>`;
    document.getElementById('messageInput').value = '';
    addNotification(`Message sent to all defendants`, 'agent');
    addNotification(`Agent ${currentUser.name}: ${content}`, 'defendant');
}

function updateAgentUI() {
    const defendantList = document.getElementById('defendantList');
    if (defendantList) {
        defendantList.innerHTML = defendants
            .filter(d => d.agentId === currentUser.id)
            .map(d => `
                <div class="defendant-details">
                    <p><strong>${d.name}</strong> (ID: ${d.id})</p>
                    <p>Last Check-In: ${d.checkins[d.checkins.length - 1]?.date || 'None'}</p>
                    <p>Risk Score: ${d.riskScore}</p>
                    <p>Contact: ${d.checkins[d.checkins.length - 1]?.contact || 'Unknown'}</p>
                    <p>Residence: ${d.checkins[d.checkins.length - 1]?.residence || 'Unknown'}</p>
                    <p>Employment: ${d.checkins[d.checkins.length - 1]?.employment || 'Unknown'}</p>
                    <p>Status: ${d.checkins[d.checkins.length - 1]?.status || 'No updates'}</p>
                    <img src="${d.mugshot}" alt="Mugshot">
                </div>`).join('');
    }

    const checkinList = document.getElementById('checkinList');
    if (checkinList) {
        checkinList.innerHTML = checkins
            .filter(c => defendants.find(d => d.id === c.defendantId && d.agentId === currentUser.id))
            .map(c => `<p>${defendants.find(d => d.id === c.defendantId).name}: ${c.date} - ${c.location}</p>`).join('');
    }

    const missedList = document.getElementById('missedList');
    if (missedList) {
        missedList.innerHTML = defendants
            .filter(d => d.agentId === currentUser.id && d.missed.length)
            .map(d => `<p>${d.name} - Missed: ${d.missed[d.missed.length - 1].date}</p>`).join('');
    }
}

function approveAgent(index) {
    const agent = pendingAgents.splice(index, 1)[0];
    agent.subscription = 'Active';
    agents.push(agent);
    logAction(`Approved ${agent.name} as agent`);
    addNotification(`${agent.name} approved`, 'agent');
    updateAdminUI();
}

function rejectAgent(index) {
    const agent = pendingAgents.splice(index, 1)[0];
    logAction(`Rejected ${agent.name}`);
    updateAdminUI();
}

function updateAdminUI() {
    const pendingAgentsDiv = document.getElementById('pendingAgents');
    if (pendingAgentsDiv) {
        pendingAgentsDiv.innerHTML = pendingAgents.map((a, index) => `
            <p>${a.name} (${a.email}, License: ${a.license}) 
            - <button onclick="approveAgent(${index})"><i class="fas fa-check"></i> Approve</button> 
            - <button onclick="rejectAgent(${index})"><i class="fas fa-times"></i> Reject</button></p>`).join('');
    }
}

function sendSystemAlert() {
    const msg = prompt('Enter system-wide alert message:');
    if (msg) {
        addNotification(`System Alert: ${msg}`, 'agent');
        addNotification(`System Alert: ${msg}`, 'defendant');
        logAction(`Admin sent alert: ${msg}`);
        alert('Alert sent to all users.');
    }
}

function exportData() {
    const data = { agents, pendingAgents, defendants, checkins, notifications, reminders, messages };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'bail-checkin-data.json';
    a.click();
    logAction('Admin exported system data');
}

function toggleAutoReminders() {
    autoReminders = !autoReminders;
    document.getElementById('autoReminderStatus').textContent = autoReminders ? 'On' : 'Off';
    logAction(`Auto-reminders ${autoReminders ? 'enabled' : 'disabled'}`);
    if (autoReminders) {
        setInterval(() => {
            agents.forEach(agent => {
                defendants
                    .filter(d => d.agentId === agent.id && d.missed.length)
                    .forEach(d => {
                        reminders.push({ defendantId: d.id, message: `Reminder: Check in required by ${new Date(Date.now() + 24 * 60 * 60 * 1000).toLocaleDateString()}`, date: new Date().toLocaleString() });
                        addNotification(`Reminder sent to ${d.name}`, 'agent');
                        addNotification(`Agent ${agent.name} requests your check-in by tomorrow`, 'defendant');
                    });
            });
        }, 24 * 60 * 60 * 1000); // Daily reminders
    }
}

function resetRiskScores() {
    defendants.forEach(d => d.riskScore = 0);
    logAction('Admin reset all risk scores');
    addNotification('All risk scores reset', 'agent');
}

function generatePerformanceReport() {
    const report = agents.map(a => `${a.name}: Defendants: ${defendants.filter(d => d.agentId === a.id).length}, Check-Ins: ${checkins.filter(c => defendants.find(d => d.id === c.defendantId && d.agentId === a.id)).length}`).join('\n');
    const blob = new Blob([report], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'agent_performance_report.txt';
    a.click();
    logAction('Admin generated performance report');
    addNotification('Performance report generated', 'agent');
}

// Subscription Check (Mock)
setInterval(() => {
    agents.forEach(a => {
        if (a.subscription === 'Pending') {
            addNotification(`${a.name}: Subscription payment due ($99/month)`, 'agent');
        }
    });
}, 60000);