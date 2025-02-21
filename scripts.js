// Simulated data storage with mock accounts
let currentUser = null;
let agents = [
    { id: "AGT99999", name: "Admin User", email: "admin@bailsafe.com", license: "FL99999", docs: "Uploaded", subscription: "Active", isAdmin: true },
    { id: "AGT88888", name: "Agent Jane", email: "jane@bailsafe.com", license: "FL88888", docs: "Uploaded", subscription: "Active", isAdmin: false }
];
let pendingAgents = [];
let defendants = [
    { id: "DEF77777", name: "Defendant Bob", agentId: "AGT88888", checkins: [], missed: [], riskScore: 0, mugshot: "https://images.unsplash.com/photo-1511367461989-f85a21fda167?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" }
];
let checkins = [];
let notifications = [];
let reminders = [];
let messages = [];
let logs = [];
let autoReminders = false;

// Utility functions
function logAction(action) {
    logs.push({ message: action, date: new Date().toLocaleString() });
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
    const logsDiv = document.getElementById('systemLogs');
    if (logsDiv) {
        logsDiv.innerHTML = logs.map(l => `<p>${l.date}: ${l.message}</p>`).join('');
    }
}

// Defendant Login
document.getElementById('defendantLoginForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    const id = document.getElementById('defendantId').value;
    currentUser = defendants.find(d => d.id === id) || { id, name: id, agentId: null, checkins: [], missed: [], riskScore: 0, mugshot: "https://images.unsplash.com/photo-1511367461989-f85a21fda167?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" };
    if (!defendants.some(d => d.id === id)) defendants.push(currentUser);
    logAction(`${currentUser.name} logged in as defendant`);
    window.location.href = '/defendant-dashboard.html';
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
    window.location.href = currentUser.isAdmin ? '/admin-dashboard.html' : '/agent-dashboard.html';
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
    window.location.href = '/index.html';
});

// Defendant Dashboard
if (window.location.pathname.endsWith('defendant-dashboard.html')) {
    document.getElementById('defendantName').textContent = currentUser?.name || 'Defendant';
    const historyDiv = document.getElementById('checkinHistory');
    historyDiv.innerHTML = currentUser.checkins.map(c => `
        <p>${c.date}: Employment: ${c.employment}, Residence: ${c.residence}, Contact: ${c.contact}</p>`).join('');

    const reminderList = document.getElementById('reminderList');
    reminderList.innerHTML = reminders
        .filter(r => r.defendantId === currentUser.id)
        .map(r => `<p><i class="fas fa-bell"></i> ${r.message} <small>${r.date}</small></p>`).join('');

    const locationList = document.getElementById('locationList');
    const locations = currentUser.checkins.reduce((acc, c) => {
        const loc = c.location.split(',')[0];
        acc[loc] = (acc[loc] || 0) + 1;
        return acc;
    }, {});
    const sortedLocations = Object.entries(locations).sort((a, b) => b[1] - a[1]);
    locationList.innerHTML = sortedLocations.map(([loc, count]) => `<p>Location: ${loc}, Visits: ${count}</p>`).join('');

    document.getElementById('checkinForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const checkin = {
            defendantId: currentUser.id,
            date: new Date().toLocaleString(),
            employment: document.getElementById('employment').value,
            residence: document.getElementById('residence').value,
            contact: document.getElementById('contact').value,
            status: document.getElementById('status').value,
            photo: document.getElementById('photo').files[0] ? 'Uploaded' : 'None',
            location: document.getElementById('locationResult').textContent
        };
        currentUser.checkins.push(checkin);
        checkins.push(checkin);
        currentUser.missed = currentUser.missed.filter(m => new Date(m.date) > new Date(Date.now() - 7 * 24 * 60 * 60 * 1000));
        historyDiv.innerHTML += `<p>${checkin.date}: Employment: ${checkin.employment}, Residence: ${checkin.residence}, Contact: ${checkin.contact}</p>`;
        logAction(`${currentUser.name} checked in`);
        addNotification(`${currentUser.name} completed check-in`, 'agent');
        addNotification(`Check-in submitted successfully`, 'defendant');
        updateLocations();
        e.target.reset();
        document.getElementById('locationResult').textContent = 'Location not shared yet.';
    });
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            document.getElementById('locationResult').textContent = `Lat: ${position.coords.latitude}, Lon: ${position.coords.longitude}`;
        }, () => {
            document.getElementById('locationResult').textContent = 'Location access denied.';
        });
    } else {
        document.getElementById('locationResult').textContent = 'Geolocation not supported.';
    }
}

function updateLocations() {
    const locationList = document.getElementById('locationList');
    if (locationList) {
        const locations = currentUser.checkins.reduce((acc, c) => {
            const loc = c.location.split(',')[0];
            acc[loc] = (acc[loc] || 0) + 1;
            return acc;
        }, {});
        const sortedLocations = Object.entries(locations).sort((a, b) => b[1] - a[1]);
        locationList.innerHTML = sortedLocations.map(([loc, count]) => `<p>Location: ${loc}, Visits: ${count}</p>`).join('');
    }
}

// Agent Dashboard Logic
if (window.location.pathname.endsWith('agent-dashboard.html')) {
    document.getElementById('agentName').textContent = currentUser?.name || 'Agent';
    document.getElementById('subscriptionStatus').textContent = currentUser?.subscription || 'Pending';

    document.querySelectorAll('.nav-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const sectionId = e.currentTarget.getAttribute('data-section');
            console.log(`Clicked agent nav button for section: ${sectionId}`);
            showSection(sectionId);
        });
    });

    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = e.currentTarget.getAttribute('data-section');
            console.log(`Clicked agent sidebar link for section: ${sectionId}`);
            showSection(sectionId);
        });
    });

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

    const agentChart = document.getElementById('agentAnalyticsChart')?.getContext('2d');
    if (agentChart) {
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

    showSection('defendants');
}

// Admin Dashboard Logic
if (window.location.pathname.endsWith('admin-dashboard.html')) {
    if (!currentUser || !currentUser.isAdmin) {
        window.location.href = '/index.html';
    }

    document.getElementById('adminName').textContent = currentUser?.name || 'Admin';

    // Add event listeners to nav buttons
    document.querySelectorAll('.nav-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const sectionId = e.currentTarget.getAttribute('data-section');
            console.log(`Clicked admin nav button for section: ${sectionId}`);
            showSection(sectionId);
        });
    });

    // Add event listeners to sidebar links
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = e.currentTarget.getAttribute('data-section');
            console.log(`Clicked admin sidebar link for section: ${sectionId}`);
            showSection(sectionId);
        });
    });

    // Approvals
    function refreshPendingAgents() {
        const pendingAgentsDiv = document.getElementById('pendingAgents');
        if (pendingAgentsDiv) {
            pendingAgentsDiv.innerHTML = pendingAgents.map((a, index) => `
                <p>${a.name} (${a.email}, License: ${a.license}) 
                - <button onclick="approveAgent(${index})"><i class="fas fa-check"></i> Approve</button> 
                - <button onclick="rejectAgent(${index})"><i class="fas fa-times"></i> Reject</button></p>`).join('');
        }
    }
    refreshPendingAgents();

    // Assignments
    const agentSelect = document.getElementById('agentId');
    if (agentSelect) {
        agentSelect.innerHTML = '<option value="">Select Agent</option>' + agents.map(a => `<option value="${a.id}">${a.name}</option>`).join('');
    }
    document.getElementById('assignForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const defendantId = document.getElementById('defendantId').value;
        const agentId = document.getElementById('agentId').value;
        const defendant = defendants.find(d => d.id === defendantId);
        if (defendant) {
            defendant.agentId = agentId;
            logAction(`Assigned ${defendant.name} to ${agents.find(a => a.id === agentId).name}`);
            addNotification(`${defendant.name} assigned to you`, 'agent');
            alert('Defendant assigned successfully.');
            refreshMembers();
        } else {
            alert('Defendant not found.');
        }
        e.target.reset();
    });

    // Members
    function refreshMembers() {
        const membersList = document.getElementById('membersList');
        if (membersList) {
            membersList.innerHTML = `
                <h4>Agents</h4>
                <table>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>
                    ${agents.map((a, index) => `
                        <tr>
                            <td>${a.id}</td>
                            <td>${a.name}</td>
                            <td>${a.email}</td>
                            <td>${a.subscription}</td>
                            <td>
                                <button onclick="suspendAgent(${index})" class="btn small ${a.subscription === 'Suspended' ? 'secondary' : ''}"><i class="fas fa-pause"></i> ${a.subscription === 'Suspended' ? 'Unsuspend' : 'Suspend'}</button>
                                <button onclick="deleteAgent(${index})" class="btn small danger"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>`).join('')}
                </table>
                <h4>Defendants</h4>
                <table>
                    <tr><th>ID</th><th>Name</th><th>Agent</th><th>Risk Score</th><th>Actions</th></tr>
                    ${defendants.map((d, index) => `
                        <tr>
                            <td>${d.id}</td>
                            <td>${d.name}</td>
                            <td>${agents.find(a => a.id === d.agentId)?.name || 'Unassigned'}</td>
                            <td>${d.riskScore}</td>
                            <td>
                                <button onclick="deleteDefendant(${index})" class="btn small danger"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>`).join('')}
                </table>
            `;
        }
    }
    refreshMembers();

    // Risk Analytics
    let riskChartInstance = null;
    function updateRiskChart() {
        const ctx = document.getElementById('riskAnalyticsChart')?.getContext('2d');
        if (!ctx) return;
        if (riskChartInstance) riskChartInstance.destroy();
        riskChartInstance = new Chart(ctx, {
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
    }
    updateRiskChart();

    // Tools
    document.getElementById('autoReminderStatus').textContent = autoReminders ? 'On' : 'Off';
    updateSystemLogs();

    // Performance
    function refreshPerformance() {
        const performanceDiv = document.getElementById('agentPerformance');
        if (performanceDiv) {
            performanceDiv.innerHTML = agents.map(a => `
                <p>${a.name}: Defendants: ${defendants.filter(d => d.agentId === a.id).length}, Check-Ins: ${checkins.filter(c => defendants.find(d => d.id === c.defendantId && d.agentId === a.id)).length}</p>`).join('');
        }
    }
    refreshPerformance();

    // Show default section
    showSection('members');
}

function showSection(sectionId) {
    console.log(`Showing section: ${sectionId}`);
    const sections = document.querySelectorAll('.section');
    sections.forEach(section => {
        section.style.display = 'none';
    });
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.style.display = 'block';
    } else {
        console.error(`Section with ID '${sectionId}' not found`);
    }
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
    const content = document.getElementById('messageInput')?.value;
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
    refreshPendingAgents();
    refreshMembers();
}

function rejectAgent(index) {
    const agent = pendingAgents.splice(index, 1)[0];
    logAction(`Rejected ${agent.name}`);
    addNotification(`${agent.name} rejected`, 'agent');
    refreshPendingAgents();
}

function suspendAgent(index) {
    const agent = agents[index];
    agent.subscription = agent.subscription === 'Suspended' ? 'Active' : 'Suspended';
    logAction(`${agent.name} ${agent.subscription === 'Suspended' ? 'suspended' : 'unsuspended'}`);
    addNotification(`${agent.name} account ${agent.subscription === 'Suspended' ? 'suspended' : 'unsuspended'}`, 'agent');
    refreshMembers();
}

function deleteAgent(index) {
    if (confirm(`Are you sure you want to delete agent ${agents[index].name}?`)) {
        const agent = agents.splice(index, 1)[0];
        logAction(`Deleted agent ${agent.name}`);
        addNotification(`Agent ${agent.name} deleted`, 'agent');
        refreshMembers();
    }
}

function deleteDefendant(index) {
    if (confirm(`Are you sure you want to delete defendant ${defendants[index].name}?`)) {
        const defendant = defendants.splice(index, 1)[0];
        logAction(`Deleted defendant ${defendant.name}`);
        addNotification(`Defendant ${defendant.name} deleted`, 'agent');
        refreshMembers();
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
    const data = { agents, pendingAgents, defendants, checkins, notifications, reminders, messages, logs };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'bailsafe-data.json';
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
        }, 24 * 60 * 60 * 1000);
    }
}

function resetRiskScores() {
    defendants.forEach(d => d.riskScore = 0);
    logAction('Admin reset all risk scores');
    addNotification('All risk scores reset', 'agent');
    updateRiskChart();
    refreshMembers();
}

function generatePerformanceReport() {
    const report = agents.map(a => `${a.name}: Defendants: ${defendants.filter(d => d.agentId === a.id).length}, Check-Ins: ${checkins.filter(c => defendants.find(d => d.id === c.defendantId && d.agentId === a.id)).length}`).join('\n');
    const blob = new Blob([report], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'bailsafe-performance-report.txt';
    a.click();
    logAction('Admin generated performance report');
    addNotification('Performance report generated', 'agent');
    refreshPerformance();
}

function clearLogs() {
    logs = [];
    logAction('Admin cleared system logs');
    updateSystemLogs();
}

function clearAllData() {
    if (confirm('Are you sure? This will clear all agents, defendants, check-ins, and logs.')) {
        agents = [];
        pendingAgents = [];
        defendants = [];
        checkins = [];
        notifications = [];
        reminders = [];
        messages = [];
        logs = [];
        logAction('Admin cleared all data');
        refreshPendingAgents();
        updateRiskChart();
        refreshPerformance();
        updateSystemLogs();
        updateNotifications('agent');
        refreshMembers();
        alert('All data cleared.');
    }
}

// Subscription Check (Mock)
setInterval(() => {
    agents.forEach(a => {
        if (a.subscription === 'Pending') {
            addNotification(`${a.name}: Subscription payment due ($99/month)`, 'agent');
        }
    });
}, 60000);