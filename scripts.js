console.log("Script.js loaded at start");

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

// Load currentUser from localStorage
currentUser = JSON.parse(localStorage.getItem('currentUser'));
console.log("Initial currentUser from localStorage:", currentUser);

// DOMContentLoaded for all logic
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded");

    try {
        // Agent Login
        const agentLoginForm = document.getElementById('agentLoginForm');
        if (agentLoginForm) {
            console.log("Agent login form found");
            agentLoginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                console.log("Agent login form submitted");
                try {
                    const id = document.getElementById('agentId').value;
                    console.log("Agent ID entered:", id);
                    currentUser = agents.find(a => a.id === id);
                    if (!currentUser) {
                        console.log(`No agent found with ID: ${id}`);
                        alert('Agent not approved yet.');
                        return;
                    }
                    console.log("Agent found:", currentUser);
                    localStorage.setItem('currentUser', JSON.stringify(currentUser));
                    console.log("currentUser set in localStorage:", JSON.parse(localStorage.getItem('currentUser')));
                    logs.push({ message: `${currentUser.name} logged in`, date: new Date().toLocaleString() });
                    const redirectUrl = currentUser.isAdmin ? '/admin-dashboard.html' : '/agent-dashboard.html';
                    console.log("Redirecting to:", redirectUrl);
                    window.location.href = redirectUrl;
                } catch (loginError) {
                    console.error("Login error:", loginError);
                }
            });
        } else {
            console.error("Agent login form not found");
        }

        // Admin Dashboard
        if (window.location.pathname.endsWith('admin-dashboard.html')) {
            console.log("Admin dashboard detected");
            currentUser = JSON.parse(localStorage.getItem('currentUser'));
            console.log("Current user in admin dashboard:", currentUser);
            if (!currentUser) {
                console.log("No currentUser, redirecting to index");
                window.location.href = '/index.html';
                return;
            }
            if (!currentUser.isAdmin) {
                console.log("User is not admin, redirecting to index");
                window.location.href = '/index.html';
                return;
            }
            console.log("Admin user confirmed, proceeding");

            document.getElementById('adminName').textContent = currentUser.name || 'Admin';

            // Members
            const membersList = document.getElementById('membersList');
            if (membersList) {
                console.log("Refreshing members list");
                console.log("Agents:", agents);
                console.log("Defendants:", defendants);
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
            } else {
                console.error("Members list element not found");
            }
        }
    } catch (error) {
        console.error("DOMContentLoaded error:", error);
    }
});

// Admin Functions
function suspendAgent(index) {
    const agent = agents[index];
    agent.subscription = agent.subscription === 'Suspended' ? 'Active' : 'Suspended';
    logs.push({ message: `${agent.name} ${agent.subscription === 'Suspended' ? 'suspended' : 'unsuspended'}`, date: new Date().toLocaleString() });
    const membersList = document.getElementById('membersList');
    if (membersList) {
        membersList.innerHTML = `
            <h4>Agents</h4>
            <table>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>
                ${agents.map((a, idx) => `
                    <tr>
                        <td>${a.id}</td>
                        <td>${a.name}</td>
                        <td>${a.email}</td>
                        <td>${a.subscription}</td>
                        <td>
                            <button onclick="suspendAgent(${idx})" class="btn small ${a.subscription === 'Suspended' ? 'secondary' : ''}"><i class="fas fa-pause"></i> ${a.subscription === 'Suspended' ? 'Unsuspend' : 'Suspend'}</button>
                            <button onclick="deleteAgent(${idx})" class="btn small danger"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>`).join('')}
            </table>
            <h4>Defendants</h4>
            <table>
                <tr><th>ID</th><th>Name</th><th>Agent</th><th>Risk Score</th><th>Actions</th></tr>
                ${defendants.map((d, idx) => `
                    <tr>
                        <td>${d.id}</td>
                        <td>${d.name}</td>
                        <td>${agents.find(a => a.id === d.agentId)?.name || 'Unassigned'}</td>
                        <td>${d.riskScore}</td>
                        <td>
                            <button onclick="deleteDefendant(${idx})" class="btn small danger"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>`).join('')}
            </table>
        `;
    }
}

function deleteAgent(index) {
    if (confirm(`Are you sure you want to delete agent ${agents[index].name}?`)) {
        const agent = agents.splice(index, 1)[0];
        logs.push({ message: `Deleted agent ${agent.name}`, date: new Date().toLocaleString() });
        const membersList = document.getElementById('membersList');
        if (membersList) {
            membersList.innerHTML = `
                <h4>Agents</h4>
                <table>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>
                    ${agents.map((a, idx) => `
                        <tr>
                            <td>${a.id}</td>
                            <td>${a.name}</td>
                            <td>${a.email}</td>
                            <td>${a.subscription}</td>
                            <td>
                                <button onclick="suspendAgent(${idx})" class="btn small ${a.subscription === 'Suspended' ? 'secondary' : ''}"><i class="fas fa-pause"></i> ${a.subscription === 'Suspended' ? 'Unsuspend' : 'Suspend'}</button>
                                <button onclick="deleteAgent(${idx})" class="btn small danger"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>`).join('')}
                </table>
                <h4>Defendants</h4>
                <table>
                    <tr><th>ID</th><th>Name</th><th>Agent</th><th>Risk Score</th><th>Actions</th></tr>
                    ${defendants.map((d, idx) => `
                        <tr>
                            <td>${d.id}</td>
                            <td>${d.name}</td>
                            <td>${agents.find(a => a.id === d.agentId)?.name || 'Unassigned'}</td>
                            <td>${d.riskScore}</td>
                            <td>
                                <button onclick="deleteDefendant(${idx})" class="btn small danger"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>`).join('')}
                </table>
            `;
        }
    }
}

function deleteDefendant(index) {
    if (confirm(`Are you sure you want to delete defendant ${defendants[index].name}?`)) {
        const defendant = defendants.splice(index, 1)[0];
        logs.push({ message: `Deleted defendant ${defendant.name}`, date: new Date().toLocaleString() });
        const membersList = document.getElementById('membersList');
        if (membersList) {
            membersList.innerHTML = `
                <h4>Agents</h4>
                <table>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>
                    ${agents.map((a, idx) => `
                        <tr>
                            <td>${a.id}</td>
                            <td>${a.name}</td>
                            <td>${a.email}</td>
                            <td>${a.subscription}</td>
                            <td>
                                <button onclick="suspendAgent(${idx})" class="btn small ${a.subscription === 'Suspended' ? 'secondary' : ''}"><i class="fas fa-pause"></i> ${a.subscription === 'Suspended' ? 'Unsuspend' : 'Suspend'}</button>
                                <button onclick="deleteAgent(${idx})" class="btn small danger"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>`).join('')}
                </table>
                <h4>Defendants</h4>
                <table>
                    <tr><th>ID</th><th>Name</th><th>Agent</th><th>Risk Score</th><th>Actions</th></tr>
                    ${defendants.map((d, idx) => `
                        <tr>
                            <td>${d.id}</td>
                            <td>${d.name}</td>
                            <td>${agents.find(a => a.id === d.agentId)?.name || 'Unassigned'}</td>
                            <td>${d.riskScore}</td>
                            <td>
                                <button onclick="deleteDefendant(${idx})" class="btn small danger"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>`).join('')}
                </table>
            `;
        }
    }
}