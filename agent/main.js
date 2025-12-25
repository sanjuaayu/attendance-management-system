// Get current date for display
function getCurrentDate() {
    const now = new Date();
    return now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Update date on all pages
// Add this inside your DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function () {
    const dateElements = document.querySelectorAll('#currentDate');
    dateElements.forEach(element => {
        element.textContent = getCurrentDate();
        
    });

    // Check if we're on a dashboard page
    if (document.querySelector('.dashboard-container')) {
        // Simulate logged in user (in real app: fetch from session/localStorage)
        const currentUser = JSON.parse(localStorage.getItem("currentUser"));
        const adminNameElement = document.getElementById('adminName');

        if (adminNameElement && currentUser) {
            adminNameElement.textContent = currentUser.fullName;
        }

        // Load existing users and attendance data
        loadUserData();
        loadAttendanceData();
    }

    // ========== Branch validation ==========
    const branchButtons = document.querySelectorAll(".branch-option");
    if (branchButtons.length > 0) {
        branchButtons.forEach(button => {
            button.addEventListener("click", function () {
                const selectedBranch = this.textContent.trim();
                const currentUser = JSON.parse(localStorage.getItem("currentUser"));

                if (!currentUser) {
                    alert("No user logged in. Please log in first.");
                    window.location.href = "index.php";
                    return;
                }

                if (currentUser.branch !== selectedBranch) {
                    alert("Please check your correct branch");
                } else {
                    alert("Welcome " + currentUser.username + "! Proceeding...");
                    window.location.href = "dashboard.html"; // Punch In/Out page
                }
            });
        });
    }
});

// Load user data from localStorage
function loadUserData() {
    const users = JSON.parse(localStorage.getItem('users')) || [];
    const userTableBody = document.getElementById('userTableBody');
    if (!userTableBody) return;
    userTableBody.innerHTML = ''; // Clear existing data

    users.forEach(user => {
        const row = document.createElement('tr');
        const usernameCell = document.createElement('td');
        const fullNameCell = document.createElement('td');
        const roleCell = document.createElement('td');
        const branchCell = document.createElement('td');
        const actionsCell = document.createElement('td');

        usernameCell.textContent = user.username;
        fullNameCell.textContent = user.fullName;
        roleCell.textContent = user.role;
        branchCell.textContent = user.branch;

        const editBtn = document.createElement('button');
        editBtn.textContent = 'Edit';
        editBtn.onclick = () => editUser(user.username);

        const deleteBtn = document.createElement('button');
        deleteBtn.textContent = 'Delete';
        deleteBtn.onclick = () => deleteUser(user.username);

        actionsCell.appendChild(editBtn);
        actionsCell.appendChild(deleteBtn);

        row.appendChild(usernameCell);
        row.appendChild(fullNameCell);
        row.appendChild(roleCell);
        row.appendChild(branchCell);
        row.appendChild(actionsCell);

        userTableBody.appendChild(row);
    });
}

// Load attendance data from localStorage
function loadAttendanceData() {
    const records = JSON.parse(localStorage.getItem('attendanceRecords')) || [];
    const reportTableBody = document.getElementById('reportTableBody');
    if (!reportTableBody) return;
    reportTableBody.innerHTML = ''; // Clear existing data

    records.forEach(record => {
        const row = document.createElement('tr');
        const employeeCell = document.createElement('td');
        const punchInCell = document.createElement('td');
        const punchOutCell = document.createElement('td');
        const durationCell = document.createElement('td');
        const statusCell = document.createElement('td');

        employeeCell.textContent = record.employee || 'Unknown';
        punchInCell.textContent = record.punchIn || '-';
        punchOutCell.textContent = record.punchOut || '-';
        durationCell.textContent = record.duration || '-';
        statusCell.textContent = record.status || 'Pending';

        row.appendChild(employeeCell);
        row.appendChild(punchInCell);
        row.appendChild(punchOutCell);
        row.appendChild(durationCell);
        row.appendChild(statusCell);

        reportTableBody.appendChild(row);
    });
}

// Add user functionality
document.getElementById('addUserBtn')?.addEventListener('click', function () {
    const username = document.getElementById('username').value;
    const fullName = document.getElementById('fullName').value;
    const role = document.getElementById('userRole').value;
    const branch = document.getElementById('userBranch').value;

    if (username && fullName) {
        const users = JSON.parse(localStorage.getItem('users')) || [];
        users.push({ username, fullName, role, branch });
        localStorage.setItem('users', JSON.stringify(users));
        loadUserData(); // Refresh user data
        alert('User added successfully!');
    } else {
        alert('Please fill in all fields.');
    }
});

// Edit user functionality (placeholder)
function editUser(username) {
    alert(`Edit functionality for ${username} would open here.`);
}

// Delete user functionality
function deleteUser(username) {
    const users = JSON.parse(localStorage.getItem('users')) || [];
    const updatedUsers = users.filter(user => user.username !== username);
    localStorage.setItem('users', JSON.stringify(updatedUsers));
    loadUserData(); // Refresh user data
    alert('User deleted successfully!');
}

// Generate report button functionality
document.getElementById('generateReportBtn')?.addEventListener('click', function () {
    const selectedDate = document.getElementById('reportDate').value;
    if (selectedDate) {
        alert(`Report generated for ${selectedDate}`);
        // In a real app, this would fetch data from the server
    } else {
        alert('Please select a date');
    }
});

// Search users functionality
document.getElementById('searchUser')?.addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#userTableBody tr');

    rows.forEach(row => {
        const username = row.cells[0].textContent.toLowerCase();
        const fullName = row.cells[1].textContent.toLowerCase();
        if (username.includes(filter) || fullName.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Placeholder for exporting reports
document.getElementById('exportReportBtn')?.addEventListener('click', function () {
    alert('Export functionality would be implemented here.');
});
