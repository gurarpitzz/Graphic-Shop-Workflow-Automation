/**
 * app.js v3.2 - Stable Mapping
 */

const API_URL = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, "/server/api.php");

let currentUser = null;
let currentOrders = [];
let currentCustomers = [];
let currentOrderId = null;
let currentView = 'grid';
let activeSection = 'orders';
let allUsers = [];

// --- AUTH ---
async function handleLogin() {
    const user = document.getElementById('username').value;
    const pass = document.getElementById('password').value;
    const captchaInp = document.getElementById('captchaInput').value;
    const captchaText = document.getElementById('captchaText').innerText;
    if (captchaInp !== captchaText) { alert("Incorrect Captcha!"); return; }
    try {
        const formData = new FormData();
        formData.append('username', user);
        formData.append('password', pass);
        formData.append('action', 'login');
        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            currentUser = data.user;
            sessionStorage.setItem('user', JSON.stringify(currentUser));
            window.location.href = 'dashboard.html';
        } else { alert(data.message); }
    } catch (e) { alert("Login Error: Server Unreachable"); }
}

function handleLogout() { sessionStorage.removeItem('user'); window.location.href = 'index.html'; }

// --- DASHBOARD CORE ---
function initDashboard() {
    const saved = sessionStorage.getItem('user');
    if (!saved) { window.location.href = 'index.html'; return; }
    currentUser = JSON.parse(saved);
    document.getElementById('userNameDisplay').textContent = `Hi ${currentUser.username}`;
    const roleBadge = document.getElementById('userRoleDisplay');
    roleBadge.textContent = currentUser.role.toUpperCase() + (currentUser.domain ? ` (${currentUser.domain})` : '');
    roleBadge.className = `role-badge role-${currentUser.role}`;

    if (currentUser.role !== 'admin' && currentUser.role !== 'accountant') {
        document.querySelectorAll('.admin-only').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.admin-accountant-only').forEach(el => el.classList.add('hidden'));
    }
    showSection('orders');
    fetchUsers(); // Pre-load team for delegation dropdowns
}

function showSection(section) {
    activeSection = section;
    document.querySelectorAll('.content-section').forEach(s => s.classList.add('hidden'));
    document.querySelectorAll('.nav-links li').forEach(l => l.classList.remove('active'));
    document.getElementById(`${section}Section`).classList.remove('hidden');
    const navItem = document.getElementById(`nav-${section}`);
    if (navItem) navItem.classList.add('active');

    if (section === 'customers') fetchCustomers();
    else if (section === 'archive') fetchOrders(1);
    else if (section === 'workforce') fetchUsers();
    else fetchOrders(0);
}

// --- WORKFORCE ---
async function fetchUsers() {
    try {
        const res = await fetch(`${API_URL}?action=fetch_all_users`);
        const data = await res.json();
        if (data.success) {
            allUsers = data.users;
            renderWorkforceTable();
        }
    } catch (e) { console.error(e); }
}

function renderWorkforceTable() {
    const body = document.getElementById('workforceTableBody');
    if (!body) return;
    body.innerHTML = '';
    allUsers.forEach(u => {
        const tr = document.createElement('tr');
        const roleClass = `role-${u.role}`;
        const domains = u.domain ? u.domain.split(',').map(d => `<span class="domain-tag">${d.trim()}</span>`).join('') : '-';
        tr.innerHTML = `
            <td><b>${u.username}</b></td>
            <td><span class="role-badge ${roleClass}">${u.role.toUpperCase()}</span></td>
            <td>${domains}</td>
            <td>
                ${u.id != currentUser.id ? `<button class="btn btn-icon" onclick="deleteUser(${u.id})" style="color:var(--accent)"><i class="fas fa-trash"></i></button>` : '<i>Self</i>'}
            </td>
        `;
        body.appendChild(tr);
    });
}

async function addUser() {
    const user = document.getElementById('newWorkerUser').value;
    const role = document.getElementById('newWorkerRole').value;
    const selectedDomains = Array.from(document.querySelectorAll('input[name="domain"]:checked')).map(cb => cb.value).join(', ');

    if (!user) { alert("Username required"); return; }
    if (role === 'worker' && !selectedDomains) { alert("Select at least one domain for workers"); return; }

    const fd = new FormData();
    fd.append('action', 'add_user');
    fd.append('username', user);
    fd.append('role', role);
    fd.append('domain', selectedDomains);
    fd.append('admin_id', currentUser.id);

    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        alert(data.message);
        document.getElementById('newWorkerUser').value = '';
        document.querySelectorAll('input[name="domain"]').forEach(cb => cb.checked = false);
        fetchUsers();
    } else alert(data.message);
}

async function deleteUser(id) {
    if (!confirm("Remove this employee?")) return;
    const fd = new FormData();
    fd.append('action', 'delete_user');
    fd.append('target_id', id);
    fd.append('admin_id', currentUser.id);

    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) fetchUsers();
    else alert(data.message);
}

function setView(view) {
    currentView = view;
    document.getElementById('orderGridContainer').className = view === 'grid' ? 'order-grid' : 'hidden';
    document.getElementById('orderTableContainer').className = view === 'table' ? '' : 'hidden';
}

function handleSearch(query) {
    query = query.toLowerCase();
    const filtered = currentOrders.filter(o =>
        (o.order_id_code && o.order_id_code.toLowerCase().includes(query)) ||
        (o.customer_name && o.customer_name.toLowerCase().includes(query)) ||
        (o.mobile_number && o.mobile_number.includes(query))
    );
    if (activeSection === 'archive') renderArchiveTable(filtered);
    else renderOrders(filtered);
}

function handleCustomerSearch(query) {
    query = query.toLowerCase();
    const filtered = currentCustomers.filter(c =>
        (c.name && c.name.toLowerCase().includes(query)) ||
        (c.mobile_number && c.mobile_number.includes(query))
    );
    renderCustomerTable(filtered);
}

function renderOrders(filtered = null) {
    const grid = document.getElementById('orderGridContainer');
    const tableBody = document.getElementById('orderTableBody');
    if (!grid || !tableBody) return;
    grid.innerHTML = ''; tableBody.innerHTML = '';
    let data = filtered || currentOrders;
    if (currentUser.role === 'worker' && currentUser.domain) {
        const myDomains = currentUser.domain.toLowerCase().split(',').map(d => d.trim());
        data = data.filter(o => {
            if (o.current_stage_name === 'Order Received') return true;

            // Delegation Check: If assigned to someone else, hide from this worker
            if (o.current_responsible_id && o.current_responsible_id != currentUser.id) return false;

            const stage = o.current_stage_name.toLowerCase();
            return myDomains.some(d => {
                const domain = d.toLowerCase().replace(/\s/g, '');

                // Bridge Machine Ops <=> Printing
                if (domain.includes('printing') || domain.includes('machine') || domain.includes('ops')) {
                    if (stage.includes('printing') || stage.includes('machine') || stage.includes('operations')) return true;
                }

                if (domain.includes('design')) return stage.includes('designing');
                if (domain.includes('fabricat') || domain.includes('weld')) return stage.includes('fabrication') || stage.includes('assembly');
                if (domain.includes('instal')) return stage.includes('install');
                if (domain.includes('stamp')) return stage.includes('stamp');

                return stage.includes(domain);
            });
        });
    }
    data.forEach(order => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${order.thumbnail_path ? `<img src="${order.thumbnail_path}" class="order-img-sm">` : `-`}</td><td>${order.order_id_code || order.id}</td><td><strong>${order.customer_name}</strong></td><td>${order.project_type || '-'}</td><td><span class="role-badge role-worker" style="font-size:0.75rem">${order.current_stage_name}</span></td><td class="admin-accountant-only">₹${(order.total_amount - order.advance_paid).toFixed(2)}</td><td><button class="btn btn-primary" style="padding:4px 10px;" onclick="openOrderModalById(${order.id})">Open</button></td>`;
        tableBody.appendChild(tr);

        const card = document.createElement('div'); card.className = 'order-card glass'; card.onclick = () => openOrderModal(order);
        card.innerHTML = `<div class="order-status">${order.current_stage_name}</div>${order.thumbnail_path ? `<img src="${order.thumbnail_path}" class="order-img">` : `<div class="order-img empty-img"><i class="fas fa-image"></i></div>`}<div class="order-info"><h4>#${order.order_id_code || order.id}</h4><p><strong>${order.customer_name}</strong></p><p style="font-size:0.8rem; color:var(--primary)">${order.project_type || 'Work'}</p></div>`;
        grid.appendChild(card);
    });
}

async function fetchOrders(isArchived = 0) {
    try {
        const res = await fetch(`${API_URL}?action=fetch_orders&archived=${isArchived}`);
        const data = await res.json();
        if (data.success) {
            currentOrders = data.orders;
            if (activeSection === 'archive') renderArchiveTable();
            else renderOrders();
        }
    } catch (e) { console.error("Fetch Error:", e); }
}

function renderArchiveTable(filtered = null) {
    const body = document.getElementById('archiveTableBody'); body.innerHTML = '';
    const data = filtered || currentOrders;
    data.forEach(o => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${o.order_id_code}</td><td>${o.customer_name}</td><td>${o.project_type}</td><td>${o.updated_at.split(' ')[0]}</td><td>₹${o.total_amount} (Paid)</td><td><button class="btn btn-secondary" onclick="openOrderModalById(${o.id})"><i class="fas fa-eye"></i> Vault</button></td>`;
        body.appendChild(tr);
    });
}

// --- CUSTOMERS ---
async function fetchCustomers() {
    const res = await fetch(`${API_URL}?action=fetch_customers`);
    const data = await res.json();
    if (data.success) {
        currentCustomers = data.customers;
        renderCustomerTable();
    }
}

function renderCustomerTable(filtered = null) {
    const body = document.getElementById('customerTableBody'); body.innerHTML = '';
    const data = filtered || currentCustomers;
    data.forEach(c => {
        const tr = document.createElement('tr');
        const scoreColor = c.actual_order_count > c.rate_request_count ? 'var(--active)' : 'var(--accent)';
        tr.innerHTML = `<td>${c.cust_id}</td><td><strong>${c.name}</strong></td><td>${c.mobile_number}</td><td>${c.actual_order_count} / ${c.rate_request_count + c.actual_order_count}</td><td><b style="color:${scoreColor}">${c.payment_pattern_score}%</b></td><td><button class="btn btn-primary" style="padding:4px 10px;" onclick="lookupCustomerOrders('${c.mobile_number}')">View History</button></td>`;
        body.appendChild(tr);
    });
}

async function lookupCustomerOrders(mobile) {
    showSection('orders');
    const res1 = await fetch(`${API_URL}?action=fetch_orders&archived=0`);
    const active = await res1.json();
    const res2 = await fetch(`${API_URL}?action=fetch_orders&archived=1`);
    const archived = await res2.json();
    currentOrders = [...(active.orders || []), ...(archived.orders || [])].filter(o => o.mobile_number == mobile);
    renderOrders();
}

// --- MODAL LOGIC ---
function openOrderModal(order) {
    currentOrderId = (order === 'new') ? null : order.id;
    document.getElementById('orderModal').classList.remove('hidden');
    switchTab('contact');

    document.getElementById('btnArchive').classList.add('hidden');
    document.getElementById('btnDelete').classList.add('hidden');

    if (order === 'new') {
        document.getElementById('displayOrderID').textContent = "NEW OPERATIVE";
        resetForm();
    } else {
        document.getElementById('displayOrderID').textContent = order.order_id_code || `#${order.id}`;
        document.getElementById('inpCustomer').value = order.customer_name || '';
        document.getElementById('inpMobile').value = order.mobile_number || '';
        document.getElementById('inpWA').value = order.whatsapp_link || '';
        document.getElementById('inpMail').value = order.mail_link || '';
        document.getElementById('inpDesc').value = order.design_description || '';
        document.getElementById('inpMaterial').value = order.project_type || '';
        document.getElementById('inpSizes').value = order.sizes || '';
        document.getElementById('inpTotal').value = order.total_amount || 0;
        document.getElementById('inpAdvance').value = order.advance_paid || 0;
        document.getElementById('calcDue').textContent = (order.total_amount - order.advance_paid).toFixed(2);
        document.getElementById('inpBill').value = order.bill_number || '';

        if (order.is_closed && !order.is_archived && currentUser.role === 'admin')
            document.getElementById('btnArchive').classList.remove('hidden');

        if (currentUser.role === 'admin' || currentUser.role === 'accountant')
            document.getElementById('btnDelete').classList.remove('hidden');

        fetchMilestones(order.id);
        fetchTrail(order.id);
        fetchMedia(order.id);
    }
}

function openOrderModalById(id) {
    const o = currentOrders.find(x => x.id == id);
    if (o) openOrderModal(o);
}

function resetForm() {
    document.querySelectorAll('.modal-table input, .modal-table textarea').forEach(el => el.value = '');
    document.getElementById('calcDue').textContent = '0.00';
    document.getElementById('milestoneRows').innerHTML = '';
}

function closeOrderModal() { document.getElementById('orderModal').classList.add('hidden'); }

function switchTab(tabId) {
    if (tabId === 'financial' && currentUser.role === 'worker') return;
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(`tab-${tabId}`).classList.remove('hidden');
    if (event && event.target && event.target.classList.contains('tab-btn')) {
        event.target.classList.add('active');
    }
}

async function saveOrder() {
    const formData = new FormData();
    formData.append('action', currentOrderId ? 'update_order' : 'create_order');
    if (currentOrderId) formData.append('id', currentOrderId);

    // Explicitly mapping IDs to Backend-friendly keys
    formData.append('customer_name', document.getElementById('inpCustomer').value);
    formData.append('mobile_number', document.getElementById('inpMobile').value);
    formData.append('whatsapp_link', document.getElementById('inpWA').value);
    formData.append('mail_link', document.getElementById('inpMail').value);
    formData.append('design_description', document.getElementById('inpDesc').value);
    formData.append('project_type', document.getElementById('inpMaterial').value);
    formData.append('material_used', document.getElementById('inpMaterial').value); // Fallback
    formData.append('sizes', document.getElementById('inpSizes').value);
    formData.append('total_amount', document.getElementById('inpTotal').value);
    formData.append('advance_paid', document.getElementById('inpAdvance').value);
    formData.append('bill_number', document.getElementById('inpBill').value);

    const thumb = document.getElementById('inpFile'); if (thumb.files[0]) formData.append('thumbnail', thumb.files[0]);
    const extra = document.getElementById('inpExtraMedia'); if (extra.files.length) { for (let i = 0; i < extra.files.length; i++) formData.append('extra_media[]', extra.files[i]); }

    try {
        const res = await fetch(API_URL, { method: 'POST', body: formData });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Malformed JSON:", text);
            alert("Server Data Error! Raw Response: " + text.substring(0, 150) + "...");
            return;
        }

        if (data.success) {
            alert("Operative Record Updated!");
            closeOrderModal();
            activeSection === 'archive' ? fetchOrders(1) : fetchOrders(0);
        } else { alert("Error: " + data.message); }
    } catch (e) {
        console.error("Fetch Error:", e);
        alert("Network Failure: Server did not respond.");
    }
}

async function fetchMilestones(id) {
    const res = await fetch(`${API_URL}?action=fetch_stages&order_id=${id}`);
    const data = await res.json();
    if (data.success) renderMilestones(data.stages);
}

function renderMilestones(stages) {
    const body = document.getElementById('milestoneRows'); body.innerHTML = '';
    stages.forEach(s => {
        const row = document.createElement('tr'); if (s.is_completed) row.className = 'milestone-row completed';

        let responsibleCell = `<td>${s.completed_by_name || '-'}</td>`;
        if (currentUser.role === 'admin') {
            const options = allUsers.map(u => `<option value="${u.id}" ${u.id == s.responsible_person_id ? 'selected' : ''}>${u.username}</option>`).join('');
            responsibleCell = `<td>
                <select class="milestone-select ${s.responsible_person_id ? 'responsible-active' : ''}" onchange="assignTask(${s.id}, this.value)">
                    <option value="null">-- Select Workforce --</option>
                    ${options}
                </select>
            </td>`;
        }

        row.innerHTML = `<td>${s.stage_name}</td>${responsibleCell}<td>${s.completed_at || '---'}</td><td style="text-align:center"><input type="checkbox" ${s.is_required ? 'checked' : ''} disabled></td><td style="text-align:center"><input type="checkbox" ${s.is_completed ? 'checked' : ''} onchange="toggleMilestone(${s.id}, this.checked)"></td>`;
        body.appendChild(row);
    });
}

async function assignTask(stageId, workerId) {
    const fd = new FormData();
    fd.append('action', 'assign_task');
    fd.append('stage_id', stageId);
    fd.append('worker_id', workerId);
    fd.append('admin_id', currentUser.id);

    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        console.log("Task reassigned");
        fetchMilestones(currentOrderId);
    }
}

async function toggleMilestone(id, checked) {
    const fd = new FormData(); fd.append('action', 'update_stage'); fd.append('stage_id', id); fd.append('completed', checked ? 1 : 0); fd.append('user_id', currentUser.id);
    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        activeSection === 'archive' ? fetchOrders(1) : fetchOrders(0);
        fetchMilestones(currentOrderId);
        fetchTrail(currentOrderId);
    }
}

async function fetchTrail(id) {
    const res = await fetch(`${API_URL}?action=fetch_stages&order_id=${id}`);
    const data = await res.json();
    if (data.success) {
        const content = document.getElementById('auditTrailContent'); content.innerHTML = '';
        data.stages.filter(s => s.is_completed).forEach(s => {
            const div = document.createElement('div'); div.className = 'audit-trail-item';
            div.innerHTML = `<strong>${s.stage_name}</strong><br><small style="color:var(--primary)">Agent: ${s.completed_by_name}</small> | <small style="color:var(--text-muted)">${s.completed_at}</small>`;
            content.appendChild(div);
        });
    }
}

async function fetchMedia(id) {
    const res = await fetch(`${API_URL}?action=fetch_media&order_id=${id}`);
    const data = await res.json();
    if (data.success) {
        const gall = document.getElementById('mediaGallery'); gall.innerHTML = '';
        const order = currentOrders.find(x => x.id == id);
        if (order && order.thumbnail_path) {
            const div = document.createElement('div'); div.className = 'media-item';
            div.innerHTML = `<img src="${order.thumbnail_path}" onclick="window.open('${order.thumbnail_path}')">`;
            gall.appendChild(div);
        }
        data.media.forEach(m => {
            const div = document.createElement('div'); div.className = 'media-item';
            div.innerHTML = `<img src="${m.file_path}" onclick="window.open('${m.file_path}')">`;
            gall.appendChild(div);
        });
    }
}

async function archiveOrder() {
    if (!confirm("Confirm Vault Transfer?")) return;
    const fd = new FormData(); fd.append('action', 'archive_order'); fd.append('id', currentOrderId);
    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { alert("Archived."); closeOrderModal(); showSection('archive'); }
}

async function deleteOrder() {
    if (!confirm("🚨 PERMANENT DELETE?\nThis action cannot be undone and will remove all associated media and audit logs.")) return;
    const fd = new FormData();
    fd.append('action', 'delete_order');
    fd.append('id', currentOrderId);
    fd.append('user_id', currentUser.id);

    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        alert("Record Expunged.");
        closeOrderModal();
        activeSection === 'archive' ? fetchOrders(1) : fetchOrders(0);
    } else { alert("Error: " + data.message); }
}

async function showDayReport() {
    const res = await fetch(`${API_URL}?action=fetch_orders`);
    const data = await res.json();
    if (data.success) {
        let msg = "Day-End Operative Summary:\n\n";
        data.orders.forEach(o => msg += `- ${o.order_id_code}: ${o.current_stage_name} (${o.customer_name})\n`);
        alert(msg);
    }
}
