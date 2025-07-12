<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON Database Manager</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        h1 { text-align: center; color: #2c3e50; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-bar { width: 50%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; color: white; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #3498db; }
        .btn-primary:hover { background-color: #2980b9; }
        .btn-success { background-color: #2ecc71; }
        .btn-success:hover { background-color: #27ae60; }
        .btn-danger { background-color: #e74c3c; }
        .btn-danger:hover { background-color: #c0392b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; word-break: break-all; }
        th { background-color: #ecf0f1; }
        tr:hover { background-color: #f9f9f9; }
        .actions { display: flex; gap: 10px; }
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: calc(100% - 20px); padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h1>SIM Data Manager</h1>
    <div class="toolbar">
        <input type="text" id="searchInput" class="search-bar" placeholder="Search by Name, Mobile, or CNIC...">
        <div>
            <button class="btn btn-primary" id="addRecordBtn">Add New Record</button>
            <button class="btn btn-success" id="uploadBtn">Upload JSON</button>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>CNIC</th>
                    <th>Operator</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data will be loaded here by JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="recordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Record</h2>
            <span class="close" id="closeModal">×</span>
        </div>
        <form id="recordForm">
            <input type="hidden" id="recordCnic" name="originalCnic">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="Name" required>
            </div>
            <div class="form-group">
                <label for="mobile">Mobile (e.g., 03001234567)</label>
                <input type="text" id="mobile" name="Mobile" required>
            </div>
            <div class="form-group">
                <label for="cnic">CNIC (e.g., 1234512345671)</label>
                <input type="text" id="cnic" name="CNIC" required>
            </div>
            <div class="form-group">
                <label for="operator">Operator</label>
                <input type="text" id="operator" name="Operator">
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="Address">
            </div>
            <button type="submit" class="btn btn-primary">Save Record</button>
        </form>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Upload JSON File</h2>
            <span class="close" id="closeUploadModal">×</span>
        </div>
        <form id="uploadForm">
            <p>This will replace all existing data. Please upload a file with the correct format.</p>
            <div class="form-group">
                <label for="jsonFile">Select .json file</label>
                <input type="file" id="jsonFile" name="jsonFile" accept=".json" required>
            </div>
            <button type="submit" class="btn btn-success">Upload and Replace</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#dataTable tbody');
    const searchInput = document.getElementById('searchInput');

    // Modals
    const recordModal = document.getElementById('recordModal');
    const uploadModal = document.getElementById('uploadModal');
    const addRecordBtn = document.getElementById('addRecordBtn');
    const uploadBtn = document.getElementById('uploadBtn');
    const closeModal = document.getElementById('closeModal');
    const closeUploadModal = document.getElementById('closeUploadModal');

    // Form
    const recordForm = document.getElementById('recordForm');
    const modalTitle = document.getElementById('modalTitle');
    const originalCnicField = document.getElementById('recordCnic');

    let records = [];

    // --- API Communication ---
    async function apiCall(action, data) {
        const formData = new FormData();
        formData.append('action', action);

        if (data) {
            for (const key in data) {
                formData.append(key, data[key]);
            }
        }

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            alert('An error occurred. Check the console for details.');
            return { status: 'error', message: 'Client-side error.' };
        }
    }

    // --- Data and Table Functions ---
    async function loadRecords() {
        const response = await apiCall('read');
        if (response.status === 'success') {
            records = response.data || [];
            renderTable(records);
        } else {
            alert('Failed to load data: ' + response.message);
        }
    }

    function renderTable(data) {
        tableBody.innerHTML = '';
        if (!data || data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No records found.</td></tr>';
            return;
        }
        data.forEach(record => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${record.Name || ''}</td>
                <td>${record.Mobile || ''}</td>
                <td>${record.CNIC || ''}</td>
                <td>${record.Operator || ''}</td>
                <td>${record.Address || ''}</td>
                <td class="actions">
                    <button class="btn btn-primary edit-btn">Edit</button>
                    <button class="btn btn-danger delete-btn">Delete</button>
                </td>
            `;
            tr.querySelector('.edit-btn').addEventListener('click', () => openEditModal(record));
            tr.querySelector('.delete-btn').addEventListener('click', () => deleteRecord(record.CNIC));
            tableBody.appendChild(tr);
        });
    }
    
    // --- Event Handlers ---
    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        const filteredRecords = records.filter(r => 
            (r.Name && r.Name.toLowerCase().includes(searchTerm)) ||
            (r.Mobile && r.Mobile.toLowerCase().includes(searchTerm)) ||
            (r.CNIC && r.CNIC.toLowerCase().includes(searchTerm))
        );
        renderTable(filteredRecords);
    });

    addRecordBtn.addEventListener('click', () => {
        recordForm.reset();
        modalTitle.textContent = 'Add Record';
        originalCnicField.value = '';
        recordModal.style.display = 'block';
    });

    uploadBtn.addEventListener('click', () => {
        uploadModal.style.display = 'block';
    });

    closeModal.onclick = () => recordModal.style.display = 'none';
    closeUploadModal.onclick = () => uploadModal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == recordModal) recordModal.style.display = 'none';
        if (event.target == uploadModal) uploadModal.style.display = 'none';
    };

    function openEditModal(record) {
        recordForm.reset();
        modalTitle.textContent = 'Edit Record';
        originalCnicField.value = record.CNIC;
        document.getElementById('name').value = record.Name || '';
        document.getElementById('mobile').value = record.Mobile || '';
        document.getElementById('cnic').value = record.CNIC || '';
        document.getElementById('operator').value = record.Operator || '';
        document.getElementById('address').value = record.Address || '';
        recordModal.style.display = 'block';
    }

    recordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const isEditing = !!originalCnicField.value;
        const action = isEditing ? 'update' : 'add';
        const formData = new FormData(recordForm);
        const data = Object.fromEntries(formData.entries());
        
        const response = await apiCall(action, data);
        if (response.status === 'success') {
            alert(response.message);
            recordModal.style.display = 'none';
            loadRecords(); // Reload data
        } else {
            alert('Error: ' + response.message);
        }
    });

    async function deleteRecord(cnic) {
        if (!confirm(`Are you sure you want to delete the record for CNIC ${cnic}?`)) {
            return;
        }
        const response = await apiCall('delete', { cnic: cnic });
        if (response.status === 'success') {
            alert(response.message);
            loadRecords(); // Reload data
        } else {
            alert('Error: ' + response.message);
        }
    }
    
    document.getElementById('uploadForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fileInput = document.getElementById('jsonFile');
        if (fileInput.files.length === 0) {
            alert('Please select a file to upload.');
            return;
        }
        const response = await apiCall('upload', { jsonFile: fileInput.files[0] });
        if (response.status === 'success') {
            alert(response.message);
            uploadModal.style.display = 'none';
            loadRecords();
        } else {
            alert('Error: ' + response.message);
        }
    });

    // Initial load
    loadRecords();
});
</script>

</body>
</html>