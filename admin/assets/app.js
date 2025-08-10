// Telegram Bot Admin Panel - Main JavaScript
const API_BASE = '/telegram-bot-admin/api'
let currentSection = 'dashboard';
let dashboardData = {};
let currentData = {
    users: [],
    accessRequests: [],
    applications: [],
    orders: [],
    files: [],
    issues: [],
    paymentMethods: [],
    activityLogs: [],
    backups: [],
    advancedStats: null
};

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('admin_token');
    if (!token) {
        window.location.href = 'index.html';
        return;
    }
    
    loadDashboardData();
});

// API Helper
async function apiCall(endpoint, options = {}) {
    const token = localStorage.getItem('admin_token');
    
    const config = {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            ...options.headers
        },
        ...options
    };
    
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, config);
        
        if (response.status === 401) {
            localStorage.removeItem('admin_token');
            window.location.href = 'index.html';
            return;
        }
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || `HTTP ${response.status}`);
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        alert('Błąd API: ' + error.message);
        throw error;
    }
}

// Navigation
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Remove active class from nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(`${sectionName}-section`).style.display = 'block';
    
    // Add active class to nav item
    document.querySelector(`[onclick="showSection('${sectionName}')"]`).classList.add('active');
    
    currentSection = sectionName;
    
    // Load section data
    loadSectionData(sectionName);
}

// Load section-specific data
async function loadSectionData(section) {
    try {
        switch (section) {
            case 'dashboard':
                await loadDashboardData();
                break;
            case 'users':
                await loadUsers();
                break;
            case 'access-requests':
                await loadAccessRequests();
                break;
            case 'applications':
                await loadApplications();
                break;
            case 'orders':
                await loadOrders();
                break;
            case 'files':
                await loadFiles();
                break;
            case 'issues':
                await loadIssues();
                break;
            case 'analytics':
                await loadAdvancedStats();
                break;
            case 'backups':
                await loadBackups();
                break;
            case 'activity':
                await loadActivityLogs();
                break;
        }
    } catch (error) {
        console.error(`Error loading ${section}:`, error);
    }
}

// Dashboard
async function loadDashboardData() {
    try {
        const stats = await apiCall('/stats');
        dashboardData = stats;
        renderDashboardStats();
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function renderDashboardStats() {
    const statsGrid = document.getElementById('statsGrid');
    const stats = [
        {
            title: 'Wszyscy użytkownicy',
            value: dashboardData.total_users,
            icon: '👥',
            section: 'users'
        },
        {
            title: 'Oczekujące żądania',
            value: dashboardData.pending_access_requests,
            icon: '✋',
            section: 'access-requests'
        },
        {
            title: 'Wszystkie zamówienia',
            value: dashboardData.total_orders,
            icon: '🛒',
            section: 'orders'
        },
        {
            title: 'Oczekujące zamówienia',
            value: dashboardData.pending_orders,
            icon: '⏱️',
            section: 'orders'
        },
        {
            title: 'Dostępne aplikacje',
            value: dashboardData.total_applications,
            icon: '📱',
            section: 'applications'
        },
        {
            title: 'Otwarte zgłoszenia',
            value: dashboardData.open_issues,
            icon: '🎫',
            section: 'issues'
        }
    ];
    
    statsGrid.innerHTML = stats.map(stat => `
        <div class="stat-card" onclick="showSection('${stat.section}')">
            <div class="stat-card-header">
                <h3>${stat.title}</h3>
                <div class="stat-icon">${stat.icon}</div>
            </div>
            <div class="stat-number">${stat.value}</div>
        </div>
    `).join('');
}

// Users
async function loadUsers() {
    try {
        currentData.users = await apiCall('/users');
        renderUsers();
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

function renderUsers() {
    const tbody = document.querySelector('#usersTable tbody');
    tbody.innerHTML = currentData.users.map(user => `
        <tr>
            <td>${user.telegram_id}</td>
            <td>
                <div>
                    <strong>${user.first_name || ''} ${user.last_name || ''}</strong>
                    <br><small>@${user.username || 'N/A'}</small>
                </div>
            </td>
            <td>
                <span class="badge ${user.is_approved ? 'badge-success' : 'badge-warning'}">
                    ${user.is_approved ? 'Zatwierdzony' : 'Oczekuje'}
                </span>
            </td>
            <td>
                <span class="badge ${user.is_admin ? 'badge-info' : 'badge-secondary'}">
                    ${user.is_admin ? 'Admin' : 'Użytkownik'}
                </span>
            </td>
            <td>${formatDate(user.registration_date)}</td>
            <td>
                ${!user.is_approved ? `<button class="btn btn-success" onclick="approveUser(${user.id})">✓ Zatwierdź</button>` : ''}
                <button class="btn" onclick="toggleAdminStatus(${user.id})">${user.is_admin ? 'Usuń admin' : 'Dodaj admin'}</button>
                <button class="btn" onclick="editUser(${user.id})">✏️ Edytuj</button>
            </td>
        </tr>
    `).join('');
}

async function approveUser(userId) {
    try {
        await apiCall(`/users/${userId}/approve`, { method: 'PUT' });
        await loadUsers();
        await loadDashboardData();
    } catch (error) {
        console.error('Error approving user:', error);
    }
}

async function toggleAdminStatus(userId) {
    try {
        await apiCall(`/users/${userId}/admin`, { method: 'PUT' });
        await loadUsers();
    } catch (error) {
        console.error('Error toggling admin status:', error);
    }
}

// Access Requests
async function loadAccessRequests() {
    try {
        currentData.accessRequests = await apiCall('/access-requests');
        renderAccessRequests();
    } catch (error) {
        console.error('Error loading access requests:', error);
    }
}

function renderAccessRequests() {
    const tbody = document.querySelector('#accessRequestsTable tbody');
    tbody.innerHTML = currentData.accessRequests.map(request => `
        <tr>
            <td>${request.telegram_id}</td>
            <td>
                <div>
                    <strong>${request.first_name || ''} ${request.last_name || ''}</strong>
                    <br><small>@${request.username || 'N/A'}</small>
                </div>
            </td>
            <td><div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">${request.request_message || 'Brak wiadomości'}</div></td>
            <td>
                <span class="badge ${
                    request.status === 'approved' ? 'badge-success' : 
                    request.status === 'rejected' ? 'badge-danger' : 'badge-warning'
                }">
                    ${request.status}
                </span>
            </td>
            <td>${formatDate(request.requested_at)}</td>
            <td>
                ${request.status === 'pending' ? `
                    <button class="btn btn-success" onclick="approveAccessRequest(${request.id})">✓ Zatwierdź</button>
                    <button class="btn btn-danger" onclick="rejectAccessRequest(${request.id})">✗ Odrzuć</button>
                ` : ''}
                <button class="btn btn-danger" onclick="deleteAccessRequest(${request.id})">🗑️ Usuń</button>
            </td>
        </tr>
    `).join('');
}

async function approveAccessRequest(requestId) {
    try {
        await apiCall(`/access-requests/${requestId}/approve`, { method: 'PUT' });
        await loadAccessRequests();
        await loadDashboardData();
    } catch (error) {
        console.error('Error approving access request:', error);
    }
}

async function rejectAccessRequest(requestId) {
    try {
        await apiCall(`/access-requests/${requestId}/reject`, { method: 'PUT' });
        await loadAccessRequests();
        await loadDashboardData();
    } catch (error) {
        console.error('Error rejecting access request:', error);
    }
}

async function deleteAccessRequest(requestId) {
    if (confirm('Czy na pewno chcesz usunąć to żądanie dostępu?')) {
        try {
            await apiCall(`/access-requests/${requestId}`, { method: 'DELETE' });
            await loadAccessRequests();
        } catch (error) {
            console.error('Error deleting access request:', error);
        }
    }
}

// Applications
async function loadApplications() {
    try {
        currentData.applications = await apiCall('/applications');
        renderApplications();
    } catch (error) {
        console.error('Error loading applications:', error);
    }
}

function renderApplications() {
    const grid = document.getElementById('applicationsGrid');
    grid.innerHTML = currentData.applications.map(app => `
        <div class="stat-card">
            <div class="stat-card-header">
                <h3>${app.name}</h3>
                <span class="badge ${app.is_active ? 'badge-success' : 'badge-secondary'}">
                    ${app.is_active ? 'Aktywna' : 'Nieaktywna'}
                </span>
            </div>
            <p style="margin: 10px 0; color: #6c757d; font-size: 14px;">${app.description || 'Brak opisu'}</p>
            <div style="margin: 15px 0;">
                <strong>Cena:</strong> ${app.price} ${app.currency || 'PLN'}<br>
                <strong>Kod:</strong> ${app.downloader_code || 'N/A'}
            </div>
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button class="btn" onclick="editApplication(${app.id})">✏️ Edytuj</button>
                <button class="btn btn-danger" onclick="deleteApplication(${app.id})">🗑️ Usuń</button>
            </div>
        </div>
    `).join('');
}

async function deleteApplication(appId) {
    if (confirm('Czy na pewno chcesz dezaktywować tę aplikację?')) {
        try {
            await apiCall(`/applications/${appId}`, { method: 'DELETE' });
            await loadApplications();
        } catch (error) {
            console.error('Error deleting application:', error);
        }
    }
}

// Orders
async function loadOrders() {
    try {
        currentData.orders = await apiCall('/orders');
        renderOrders();
    } catch (error) {
        console.error('Error loading orders:', error);
    }
}

function renderOrders() {
    const tbody = document.querySelector('#ordersTable tbody');
    tbody.innerHTML = currentData.orders.map(order => `
        <tr>
            <td>#${order.id}</td>
            <td>
                ${order.user ? `
                    <div>
                        <strong>${order.user.first_name} ${order.user.last_name}</strong>
                        <br><small>@${order.user.username}</small>
                    </div>
                ` : 'Nieznany użytkownik'}
            </td>
            <td>${order.application ? order.application.name : 'Nieznana aplikacja'}</td>
            <td>
                <span class="badge ${
                    order.status === 'completed' ? 'badge-success' :
                    order.status === 'processing' ? 'badge-info' :
                    order.status === 'pending' ? 'badge-warning' : 'badge-danger'
                }">
                    ${order.status}
                </span>
            </td>
            <td>${formatDate(order.order_date)}</td>
            <td>
                <select onchange="updateOrderStatus(${order.id}, this.value)" class="btn">
                    <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Oczekujące</option>
                    <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Przetwarzane</option>
                    <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Zakończone</option>
                    <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Anulowane</option>
                </select>
                <button class="btn btn-danger" onclick="deleteOrder(${order.id})">🗑️ Usuń</button>
            </td>
        </tr>
    `).join('');
}

async function updateOrderStatus(orderId, status) {
    try {
        await apiCall(`/orders/${orderId}`, {
            method: 'PUT',
            body: JSON.stringify({ status })
        });
        await loadOrders();
        await loadDashboardData();
    } catch (error) {
        console.error('Error updating order status:', error);
    }
}

async function deleteOrder(orderId) {
    if (confirm('Czy na pewno chcesz usunąć to zamówienie?')) {
        try {
            await apiCall(`/orders/${orderId}`, { method: 'DELETE' });
            await loadOrders();
        } catch (error) {
            console.error('Error deleting order:', error);
        }
    }
}

// Files
async function loadFiles() {
    try {
        currentData.files = await apiCall('/files');
        renderFiles();
    } catch (error) {
        console.error('Error loading files:', error);
    }
}

function renderFiles() {
    const tbody = document.querySelector('#filesTable tbody');
    tbody.innerHTML = currentData.files.map(file => `
        <tr>
            <td>📄 ${file.file_name}</td>
            <td>${file.application_name || 'N/A'}</td>
            <td><span class="badge badge-secondary">${file.downloader_code || 'N/A'}</span></td>
            <td>
                <span class="badge ${file.file_type === 'app' ? 'badge-info' : 'badge-secondary'}">
                    ${file.file_type}
                </span>
            </td>
            <td>
                ${file.user ? `
                    <div>
                        <strong>${file.user.first_name} ${file.user.last_name}</strong>
                        <br><small>@${file.user.username}</small>
                    </div>
                ` : 'Nieznany użytkownik'}
            </td>
            <td>
                <span class="badge ${file.is_active ? 'badge-success' : 'badge-danger'}">
                    ${file.is_active ? 'Aktywny' : 'Nieaktywny'}
                </span>
            </td>
            <td>${formatDate(file.created_at)}</td>
            <td>
                ${file.file_url ? `<button class="btn" onclick="window.open('${file.file_url}', '_blank')">⬇️ Pobierz</button>` : ''}
                <button class="btn" onclick="editFile(${file.id})">✏️ Edytuj</button>
                <button class="btn btn-danger" onclick="deleteFile(${file.id})">🗑️ Usuń</button>
            </td>
        </tr>
    `).join('');
}

async function deleteFile(fileId) {
    if (confirm('Czy na pewno chcesz usunąć ten plik?')) {
        try {
            await apiCall(`/files/${fileId}`, { method: 'DELETE' });
            await loadFiles();
        } catch (error) {
            console.error('Error deleting file:', error);
        }
    }
}

// Issues
async function loadIssues() {
    try {
        currentData.issues = await apiCall('/issues');
        renderIssues();
    } catch (error) {
        console.error('Error loading issues:', error);
    }
}

function renderIssues() {
    const tbody = document.querySelector('#issuesTable tbody');
    tbody.innerHTML = currentData.issues.map(issue => `
        <tr>
            <td>#${issue.id}</td>
            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="${issue.title}">${issue.title}</td>
            <td>
                ${issue.user ? `
                    <div>
                        <strong>${issue.user.first_name} ${issue.user.last_name}</strong>
                        <br><small>@${issue.user.username}</small>
                    </div>
                ` : 'Nieznany użytkownik'}
            </td>
            <td>
                <span class="badge ${
                    issue.status === 'resolved' ? 'badge-success' :
                    issue.status === 'in_progress' ? 'badge-info' :
                    issue.status === 'open' ? 'badge-warning' : 'badge-secondary'
                }">
                    ${issue.status}
                </span>
            </td>
            <td>
                <span class="badge ${
                    issue.priority === 'urgent' ? 'badge-danger' :
                    issue.priority === 'high' ? 'badge-warning' :
                    issue.priority === 'medium' ? 'badge-info' : 'badge-secondary'
                }">
                    ${issue.priority}
                </span>
            </td>
            <td>${formatDate(issue.created_at)}</td>
            <td>
                <button class="btn" onclick="editIssue(${issue.id})">✏️ Edytuj</button>
                <button class="btn btn-danger" onclick="deleteIssue(${issue.id})">🗑️ Usuń</button>
            </td>
        </tr>
    `).join('');
}

async function deleteIssue(issueId) {
    if (confirm('Czy na pewno chcesz usunąć to zgłoszenie?')) {
        try {
            await apiCall(`/issues/${issueId}`, { method: 'DELETE' });
            await loadIssues();
        } catch (error) {
            console.error('Error deleting issue:', error);
        }
    }
}

// Advanced Analytics
async function loadAdvancedStats() {
    try {
        currentData.advancedStats = await apiCall('/stats/advanced');
        renderAdvancedStats();
    } catch (error) {
        console.error('Error loading advanced stats:', error);
    }
}

function renderAdvancedStats() {
    const chartsGrid = document.getElementById('chartsGrid');
    
    chartsGrid.innerHTML = `
        <div class="chart-card">
            <h3>📈 Trend rejestracji użytkowników</h3>
            <canvas id="userTrendsChart" width="400" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h3>🥧 Status zamówień</h3>
            <canvas id="orderStatusChart" width="400" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h3>📊 Popularność aplikacji</h3>
            <canvas id="appPopularityChart" width="400" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h3>💰 Przychody miesięczne</h3>
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>
    `;
    
    // Wait for DOM update then render charts
    setTimeout(() => {
        renderCharts();
    }, 100);
}

function renderCharts() {
    const stats = currentData.advancedStats;
    if (!stats) return;
    
    // User registration trends
    new Chart(document.getElementById('userTrendsChart'), {
        type: 'line',
        data: {
            labels: stats.user_registration_trends.map(item => new Date(item.date).toLocaleDateString('pl-PL')),
            datasets: [{
                label: 'Nowi użytkownicy',
                data: stats.user_registration_trends.map(item => item.count),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } }
        }
    });
    
    // Order status distribution
    new Chart(document.getElementById('orderStatusChart'), {
        type: 'doughnut',
        data: {
            labels: stats.order_status_distribution.map(item => item.status),
            datasets: [{
                data: stats.order_status_distribution.map(item => item.count),
                backgroundColor: [
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    
    // Application popularity
    new Chart(document.getElementById('appPopularityChart'), {
        type: 'bar',
        data: {
            labels: stats.application_popularity.map(item => item.name),
            datasets: [{
                label: 'Zamówienia',
                data: stats.application_popularity.map(item => item.order_count),
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    
    // Monthly revenue
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: stats.monthly_revenue.map(item => item.month),
            datasets: [{
                label: 'Przychód (PLN)',
                data: stats.monthly_revenue.map(item => item.revenue),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Liczba zamówień',
                data: stats.monthly_revenue.map(item => item.order_count),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { type: 'linear', display: true, position: 'left' },
                y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });
}

// Database Backup
async function loadBackups() {
    try {
        const response = await apiCall('/backup/list');
        currentData.backups = response.backups || [];
        renderBackups();
    } catch (error) {
        console.error('Error loading backups:', error);
    }
}

function renderBackups() {
    const tbody = document.querySelector('#backupsTable tbody');
    
    if (currentData.backups.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 40px; color: #6c757d;">Brak dostępnych backupów. Kliknij "Utwórz backup" aby utworzyć pierwszą kopię zapasową.</td></tr>';
        return;
    }
    
    tbody.innerHTML = currentData.backups.map(backup => `
        <tr>
            <td>💾 ${backup.filename}</td>
            <td><span class="badge badge-info">${formatFileSize(backup.size)}</span></td>
            <td>${formatDate(backup.created_at)}</td>
            <td>
                <button class="btn btn-danger" onclick="deleteBackup('${backup.filename}')">🗑️ Usuń</button>
            </td>
        </tr>
    `).join('');
}

async function createBackup() {
    const btn = document.getElementById('createBackupBtn');
    const originalText = btn.textContent;
    
    btn.textContent = 'Tworzenie...';
    btn.disabled = true;
    
    try {
        const response = await apiCall('/backup/create', { method: 'POST' });
        alert('Backup utworzony pomyślnie: ' + response.backup_filename);
        await loadBackups();
    } catch (error) {
        console.error('Error creating backup:', error);
        alert('Błąd podczas tworzenia backupu: ' + error.message);
    }
    
    btn.textContent = originalText;
    btn.disabled = false;
}

async function deleteBackup(filename) {
    if (confirm(`Czy na pewno chcesz usunąć backup: ${filename}?`)) {
        try {
            await apiCall(`/backup/${filename}`, { method: 'DELETE' });
            await loadBackups();
        } catch (error) {
            console.error('Error deleting backup:', error);
        }
    }
}

// Activity Logs
async function loadActivityLogs() {
    try {
        currentData.activityLogs = await apiCall('/activity-logs?limit=50');
        renderActivityLogs();
    } catch (error) {
        console.error('Error loading activity logs:', error);
    }
}

function renderActivityLogs() {
    const tbody = document.querySelector('#activityLogsTable tbody');
    
    if (currentData.activityLogs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #6c757d;">Brak logów aktywności</td></tr>';
        return;
    }
    
    tbody.innerHTML = currentData.activityLogs.map(log => `
        <tr>
            <td>${formatDateTime(log.created_at)}</td>
            <td>
                ${log.admin ? `
                    <div>
                        <strong>${log.admin.first_name} ${log.admin.last_name}</strong>
                        <br><small>@${log.admin.username}</small>
                    </div>
                ` : 'Nieznany admin'}
            </td>
            <td>
                <span class="badge ${
                    log.action_type === 'login' ? 'badge-success' :
                    log.action_type === 'create' ? 'badge-info' :
                    log.action_type === 'update' ? 'badge-warning' :
                    log.action_type === 'delete' ? 'badge-danger' : 'badge-secondary'
                }">
                    ${log.action_type}
                </span>
            </td>
            <td>
                ${log.resource_type ? `
                    <span class="badge badge-secondary">${log.resource_type}</span>
                    ${log.resource_id ? `<small>#${log.resource_id}</small>` : ''}
                ` : '-'}
            </td>
            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;" title="${log.description}">${log.description}</td>
            <td><code style="font-size: 12px;">${log.ip_address || '-'}</code></td>
        </tr>
    `).join('');
}

async function clearActivityLogs() {
    if (confirm('Czy na pewno chcesz wyczyścić logi aktywności starsze niż 30 dni?')) {
        try {
            const response = await apiCall('/activity-logs/clear', { method: 'DELETE' });
            alert(response.message);
            await loadActivityLogs();
        } catch (error) {
            console.error('Error clearing activity logs:', error);
        }
    }
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('pl-PL');
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('pl-PL');
}

function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    
    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

function filterTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let row of rows) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm.toLowerCase()) ? '' : 'none';
    }
}

function filterOrders() {
    const filter = document.getElementById('orderStatusFilter').value;
    const rows = document.querySelectorAll('#ordersTable tbody tr');
    
    rows.forEach(row => {
        if (filter === 'all') {
            row.style.display = '';
        } else {
            const statusCell = row.cells[3];
            const status = statusCell.textContent.trim().toLowerCase();
            row.style.display = status.includes(filter) ? '' : 'none';
        }
    });
}

// Global functions
function refreshData() {
    loadSectionData(currentSection);
}

function logout() {
    localStorage.removeItem('admin_token');
    window.location.href = 'index.html';
}

// Application Modal Functions
function showAddApplicationModal() {
    document.getElementById('applicationModalTitle').textContent = 'Dodaj aplikację';
    document.getElementById('app_id').value = '';
    document.getElementById('applicationForm').reset();
    document.getElementById('app_is_active').checked = true;
    document.getElementById('applicationModal').style.display = 'block';
}

function editApplication(appId) {
    const app = currentData.applications.find(a => a.id == appId);
    if (!app) return;
    
    document.getElementById('applicationModalTitle').textContent = 'Edytuj aplikację';
    document.getElementById('app_id').value = app.id;
    document.getElementById('app_name').value = app.name || '';
    document.getElementById('app_description').value = app.description || '';
    document.getElementById('app_price').value = app.price || '';
    document.getElementById('app_currency').value = app.currency || 'PLN';
    document.getElementById('app_downloader_code').value = app.downloader_code || '';
    document.getElementById('app_panel_url').value = app.panel_url || '';
    document.getElementById('app_is_active').checked = app.is_active == 1;
    document.getElementById('applicationModal').style.display = 'block';
}

function closeApplicationModal() {
    document.getElementById('applicationModal').style.display = 'none';
}

async function saveApplication() {
    const formData = {
        name: document.getElementById('app_name').value,
        description: document.getElementById('app_description').value,
        price: parseFloat(document.getElementById('app_price').value),
        currency: document.getElementById('app_currency').value,
        downloader_code: document.getElementById('app_downloader_code').value,
        panel_url: document.getElementById('app_panel_url').value,
        is_active: document.getElementById('app_is_active').checked ? 1 : 0
    };
    
    const appId = document.getElementById('app_id').value;
    
    try {
        if (appId) {
            await apiCall(`/applications/${appId}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
        } else {
            await apiCall('/applications', {
                method: 'POST',
                body: JSON.stringify(formData)
            });
        }
        
        closeApplicationModal();
        await loadApplications();
        await loadDashboardData();
        alert('Aplikacja została zapisana pomyślnie!');
    } catch (error) {
        console.error('Error saving application:', error);
        alert('Błąd podczas zapisywania aplikacji: ' + error.message);
    }
}

// Order Modal Functions
async function showAddOrderModal() {
    document.getElementById('orderModalTitle').textContent = 'Dodaj zamówienie';
    document.getElementById('order_id').value = '';
    document.getElementById('orderForm').reset();
    
    await loadUsersForSelect();
    await loadApplicationsForSelect();
    
    document.getElementById('orderModal').style.display = 'block';
}

async function loadUsersForSelect() {
    try {
        const users = await apiCall('/users');
        const selects = ['order_user_id', 'file_user_id', 'issue_user_id'];
        
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = '<option value="">Wybierz użytkownika</option>';
                users.forEach(user => {
                    const option = `<option value="${user.id}">${user.first_name || ''} ${user.last_name || ''} (@${user.username || 'N/A'})</option>`;
                    select.innerHTML += option;
                });
            }
        });
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

async function loadApplicationsForSelect() {
    try {
        const apps = await apiCall('/applications');
        const select = document.getElementById('order_application_id');
        
        select.innerHTML = '<option value="">Wybierz aplikację</option>';
        
        apps.filter(app => app.is_active).forEach(app => {
            select.innerHTML += `<option value="${app.id}">${app.name} (${app.price} ${app.currency || 'PLN'})</option>`;
        });
    } catch (error) {
        console.error('Error loading applications:', error);
    }
}

function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}

async function saveOrder() {
    const formData = {
        user_id: parseInt(document.getElementById('order_user_id').value),
        application_id: parseInt(document.getElementById('order_application_id').value),
        status: document.getElementById('order_status').value,
        logo_filename: document.getElementById('order_logo_filename').value,
        notes: document.getElementById('order_notes').value
    };
    
    const orderId = document.getElementById('order_id').value;
    
    try {
        if (orderId) {
            await apiCall(`/orders/${orderId}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
        } else {
            await apiCall('/orders', {
                method: 'POST',
                body: JSON.stringify(formData)
            });
        }
        
        closeOrderModal();
        await loadOrders();
        await loadDashboardData();
        alert('Zamówienie zostało zapisane pomyślnie!');
    } catch (error) {
        console.error('Error saving order:', error);
        alert('Błąd podczas zapisywania zamówienia: ' + error.message);
    }
}

// User Modal Functions
function editUser(userId) {
    const user = currentData.users.find(u => u.id == userId);
    if (!user) return;
    
    document.getElementById('user_id').value = user.id;
    document.getElementById('user_first_name').value = user.first_name || '';
    document.getElementById('user_last_name').value = user.last_name || '';
    document.getElementById('user_username').value = user.username || '';
    document.getElementById('user_telegram_id').value = user.telegram_id;
    document.getElementById('user_is_approved').checked = user.is_approved == 1;
    document.getElementById('user_is_admin').checked = user.is_admin == 1;
    document.getElementById('userModal').style.display = 'block';
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

async function saveUser() {
    const userId = document.getElementById('user_id').value;
    const formData = {
        first_name: document.getElementById('user_first_name').value,
        last_name: document.getElementById('user_last_name').value,
        username: document.getElementById('user_username').value,
        is_approved: document.getElementById('user_is_approved').checked ? 1 : 0,
        is_admin: document.getElementById('user_is_admin').checked ? 1 : 0
    };
    
    try {
        await apiCall(`/users/${userId}`, {
            method: 'PUT',
            body: JSON.stringify(formData)
        });
        
        closeUserModal();
        await loadUsers();
        await loadDashboardData();
        alert('Użytkownik został zaktualizowany pomyślnie!');
    } catch (error) {
        console.error('Error saving user:', error);
        alert('Błąd podczas zapisywania użytkownika: ' + error.message);
    }
}

// File Modal Functions
async function showAddFileModal() {
    document.getElementById('fileModalTitle').textContent = 'Dodaj plik';
    document.getElementById('file_id').value = '';
    document.getElementById('fileForm').reset();
    document.getElementById('file_is_active').checked = true;
    
    await loadUsersForSelect();
    
    document.getElementById('fileModal').style.display = 'block';
}

function editFile(fileId) {
    const file = currentData.files.find(f => f.id == fileId);
    if (!file) return;
    
    document.getElementById('fileModalTitle').textContent = 'Edytuj plik';
    document.getElementById('file_id').value = file.id;
    document.getElementById('file_name').value = file.file_name || '';
    document.getElementById('file_application_name').value = file.application_name || '';
    document.getElementById('file_downloader_code').value = file.downloader_code || '';
    document.getElementById('file_url').value = file.file_url || '';
    document.getElementById('file_type').value = file.file_type || 'file';
    document.getElementById('file_user_id').value = file.user_id || '';
    document.getElementById('file_is_active').checked = file.is_active == 1;
    
    loadUsersForSelect().then(() => {
        document.getElementById('file_user_id').value = file.user_id || '';
    });
    
    document.getElementById('fileModal').style.display = 'block';
}

function closeFileModal() {
    document.getElementById('fileModal').style.display = 'none';
}

async function saveFile() {
    const formData = {
        user_id: parseInt(document.getElementById('file_user_id').value),
        application_name: document.getElementById('file_application_name').value,
        downloader_code: document.getElementById('file_downloader_code').value,
        file_url: document.getElementById('file_url').value,
        file_name: document.getElementById('file_name').value,
        file_type: document.getElementById('file_type').value,
        is_active: document.getElementById('file_is_active').checked ? 1 : 0
    };
    
    const fileId = document.getElementById('file_id').value;
    
    try {
        if (fileId) {
            await apiCall(`/files/${fileId}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
        } else {
            await apiCall('/files', {
                method: 'POST',
                body: JSON.stringify(formData)
            });
        }
        
        closeFileModal();
        await loadFiles();
        alert('Plik został zapisany pomyślnie!');
    } catch (error) {
        console.error('Error saving file:', error);
        alert('Błąd podczas zapisywania pliku: ' + error.message);
    }
}

// Issue Modal Functions
async function showAddIssueModal() {
    document.getElementById('issueModalTitle').textContent = 'Dodaj zgłoszenie';
    document.getElementById('issue_id').value = '';
    document.getElementById('issueForm').reset();
    document.getElementById('issue_priority').value = 'medium';
    document.getElementById('issue_status').value = 'open';
    
    await loadUsersForSelect();
    
    document.getElementById('issueModal').style.display = 'block';
}

function editIssue(issueId) {
    const issue = currentData.issues.find(i => i.id == issueId);
    if (!issue) return;
    
    document.getElementById('issueModalTitle').textContent = 'Edytuj zgłoszenie';
    document.getElementById('issue_id').value = issue.id;
    document.getElementById('issue_title').value = issue.title || '';
    document.getElementById('issue_description').value = issue.description || '';
    document.getElementById('issue_priority').value = issue.priority || 'medium';
    document.getElementById('issue_status').value = issue.status || 'open';
    document.getElementById('issue_user_id').value = issue.user_id || '';
    
    loadUsersForSelect().then(() => {
        document.getElementById('issue_user_id').value = issue.user_id || '';
    });
    
    document.getElementById('issueModal').style.display = 'block';
}

function closeIssueModal() {
    document.getElementById('issueModal').style.display = 'none';
}

async function saveIssue() {
    const formData = {
        user_id: parseInt(document.getElementById('issue_user_id').value),
        title: document.getElementById('issue_title').value,
        description: document.getElementById('issue_description').value,
        priority: document.getElementById('issue_priority').value,
        status: document.getElementById('issue_status').value
    };
    
    const issueId = document.getElementById('issue_id').value;
    
    try {
        if (issueId) {
            await apiCall(`/issues/${issueId}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });
        } else {
            await apiCall('/issues', {
                method: 'POST',
                body: JSON.stringify(formData)
            });
        }
        
        closeIssueModal();
        await loadIssues();
        alert('Zgłoszenie zostało zapisane pomyślnie!');
    } catch (error) {
        console.error('Error saving issue:', error);
        alert('Błąd podczas zapisywania zgłoszenia: ' + error.message);
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = ['applicationModal', 'orderModal', 'userModal', 'fileModal', 'issueModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}