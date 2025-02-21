console.log("Script.js loaded at start");

// Simulated data storage with mock accounts
let currentUser = null;
let agents = [
    { id: "AGT99999", name: "Admin User", email: "admin@bailsafe.com", license: "FL99999", docs: "Uploaded", subscription: "Active", isAdmin: true },
    { id: "AGT88888", name: "Agent Jane", email: "jane@bailsafe.com", license: "FL88888", docs: "Uploaded", subscription: "Active", isAdmin: false }
];
let defendants = [
    { id: "DEF77777", name: "Defendant Bob", agentId: "AGT88888", checkins: [], missed: [], riskScore: 0, mugshot: "https://images.unsplash.com/photo-1511367461989-f85a21fda167?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" }
];

// Load currentUser from localStorage
currentUser = JSON.parse(localStorage.getItem('currentUser'));
console.log("Initial currentUser from localStorage:", currentUser);

// Wait for DOM to ensure form is available
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded");

    // Agent Login
    const agentLoginForm = document.getElementById('agentLoginForm');
    if (agentLoginForm) {
        console.log("Agent login form found");
        agentLoginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.log("Agent login form submitted");
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
            console.log("Redirecting to:", currentUser.isAdmin ? '/admin-dashboard.html' : '/agent-dashboard.html');
            window.location.href = currentUser.isAdmin ? '/admin-dashboard.html' : '/agent-dashboard.html';
        });
    } else {
        console.error("Agent login form not found");
    }

    // Admin Dashboard (minimal check)
    if (window.location.pathname.endsWith('admin-dashboard.html')) {
        console.log("Admin dashboard detected");
        currentUser = JSON.parse(localStorage.getItem('currentUser'));
        console.log("Current user in admin dashboard:", currentUser);
        if (!currentUser || !currentUser.isAdmin) {
            console.log("No currentUser or not admin, redirecting to index");
            window.location.href = '/index.html';
            return;
        }
        console.log("Admin user confirmed, displaying dashboard");
        document.getElementById('adminName').textContent = currentUser.name || 'Admin';
    }
});