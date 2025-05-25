document.addEventListener('DOMContentLoaded', () => {
    // --- Globals & Data Storage ---
    let patients = JSON.parse(localStorage.getItem('patients')) || [];
    let visits = JSON.parse(localStorage.getItem('visits')) || []; // { visitId, patientId, patientName, reason, checkInTime, checkOutTime, doctorNotes, status: 'checked-in'/'checked-out' }

    // --- UI Elements ---
    const sections = document.querySelectorAll('main section');
    const navLinks = document.querySelectorAll('nav a');

    const patientRegistrationForm = document.getElementById('patientRegistrationForm');
    const registrationMessage = document.getElementById('registrationMessage');

    const checkInForm = document.getElementById('checkInForm');
    const checkInPatientIdSelect = document.getElementById('checkInPatientId');
    const checkInMessage = document.getElementById('checkInMessage');

    const checkedInPatientsListDiv = document.getElementById('checkedInPatientsList');
    const checkOutMessage = document.getElementById('checkOutMessage');

    const currentlyCheckedInCountEl = document.getElementById('currentlyCheckedInCount');
    const totalVisitsTodayCountEl = document.getElementById('totalVisitsTodayCount');
    const recentCheckInsListEl = document.getElementById('recentCheckInsList');
    const recentCheckOutsListEl = document.getElementById('recentCheckOutsList');

    const btnGoToRegister = document.getElementById('btnGoToRegister');
    const btnGoToCheckIn = document.getElementById('btnGoToCheckIn');
    const inlineNavLinks = document.querySelectorAll('.inline-nav');

    // --- Helper Functions ---
    function showSection(sectionId) {
        sections.forEach(section => {
            section.classList.remove('active-section');
            section.classList.add('hidden-section');
        });
        document.getElementById(sectionId).classList.add('active-section');
        document.getElementById(sectionId).classList.remove('hidden-section');

        navLinks.forEach(link => {
            link.classList.remove('active-nav');
            if (link.dataset.section === sectionId) {
                link.classList.add('active-nav');
            }
        });
    }

    function displayMessage(element, message, type) {
        element.textContent = message;
        element.className = `message ${type}`; // success or error
        setTimeout(() => {
            element.textContent = '';
            element.className = 'message';
        }, 3000);
    }

    function generateUniqueId(prefix = '') {
        return prefix + Date.now().toString(36) + Math.random().toString(36).substring(2, 7);
    }

    function savePatients() {
        localStorage.setItem('patients', JSON.stringify(patients));
    }

    function saveVisits() {
        localStorage.setItem('visits', JSON.stringify(visits));
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    function isToday(dateString) {
        if (!dateString) return false;
        const date = new Date(dateString);
        const today = new Date();
        return date.getFullYear() === today.getFullYear() &&
               date.getMonth() === today.getMonth() &&
               date.getDate() === today.getDate();
    }


    // --- Patient Registration ---
    function handlePatientRegistration(event) {
        event.preventDefault();
        const name = document.getElementById('patientName').value.trim();
        const dob = document.getElementById('dob').value;
        const phone = document.getElementById('phone').value.trim();
        const emergencyContact = document.getElementById('emergencyContact').value.trim();

        if (!name || !dob || !phone || !emergencyContact) {
            displayMessage(registrationMessage, 'All fields are required.', 'error');
            return;
        }

        // Basic duplicate check (e.g., by name and DOB)
        const isDuplicate = patients.some(p => p.name.toLowerCase() === name.toLowerCase() && p.dob === dob);
        if (isDuplicate) {
            displayMessage(registrationMessage, 'Patient with this name and DOB already exists.', 'error');
            return;
        }

        const newPatient = {
            id: generateUniqueId('P'),
            name,
            dob,
            phone,
            emergencyContact
        };
        patients.push(newPatient);
        savePatients();
        displayMessage(registrationMessage, 'Patient registered successfully!', 'success');
        patientRegistrationForm.reset();
        populatePatientSelect(); // Update dropdowns
    }

    // --- Check-In System ---
    function populatePatientSelect() {
        checkInPatientIdSelect.innerHTML = '<option value="">-- Select Patient --</option>'; // Clear existing
        patients.forEach(patient => {
            const option = document.createElement('option');
            option.value = patient.id;
            option.textContent = `${patient.name} (ID: ${patient.id})`;
            checkInPatientIdSelect.appendChild(option);
        });
    }

    function handleCheckIn(event) {
        event.preventDefault();
        const patientId = checkInPatientIdSelect.value;
        const reason = document.getElementById('reasonForVisit').value;

        if (!patientId || !reason) {
            displayMessage(checkInMessage, 'Please select a patient and reason for visit.', 'error');
            return;
        }

        // Check if patient is already checked-in
        const alreadyCheckedIn = visits.find(v => v.patientId === patientId && v.status === 'checked-in');
        if (alreadyCheckedIn) {
             displayMessage(checkInMessage, 'This patient is already checked in.', 'error');
             return;
        }

        const patient = patients.find(p => p.id === patientId);
        if (!patient) {
            displayMessage(checkInMessage, 'Patient not found.', 'error'); // Should not happen if select is populated correctly
            return;
        }

        const newVisit = {
            visitId: generateUniqueId('V'),
            patientId,
            patientName: patient.name,
            reason,
            checkInTime: new Date().toISOString(),
            checkOutTime: null,
            doctorNotes: '',
            status: 'checked-in'
        };
        visits.push(newVisit);
        saveVisits();
        displayMessage(checkInMessage, `${patient.name} checked in successfully.`, 'success');
        checkInForm.reset();
        renderCheckedInPatients();
        renderDashboard();
    }

    // --- Check-Out System ---
    function renderCheckedInPatients() {
        checkedInPatientsListDiv.innerHTML = ''; // Clear current list
        const currentlyCheckedIn = visits.filter(v => v.status === 'checked-in');

        if (currentlyCheckedIn.length === 0) {
            checkedInPatientsListDiv.innerHTML = '<p>No patients currently checked in.</p>';
            return;
        }

        currentlyCheckedIn.forEach(visit => {
            const patient = patients.find(p => p.id === visit.patientId);
            const itemDiv = document.createElement('div');
            itemDiv.className = 'patient-item';
            itemDiv.innerHTML = `
                <h4>${patient ? patient.name : 'Unknown Patient'} (ID: ${visit.patientId})</h4>
                <p><strong>Reason:</strong> ${visit.reason}</p>
                <p><strong>Checked In:</strong> ${formatDate(visit.checkInTime)}</p>
                <div class="form-group">
                    <label for="notes-${visit.visitId}">Doctor Notes:</label>
                    <textarea id="notes-${visit.visitId}" rows="3"></textarea>
                </div>
                <button class="checkout-btn" data-visit-id="${visit.visitId}">Check Out</button>
            `;
            checkedInPatientsListDiv.appendChild(itemDiv);
        });

        // Add event listeners to new checkout buttons
        document.querySelectorAll('.checkout-btn').forEach(button => {
            button.addEventListener('click', handleCheckOut);
        });
    }

    function handleCheckOut(event) {
        const visitId = event.target.dataset.visitId;
        const notesTextArea = document.getElementById(`notes-${visitId}`);
        const doctorNotes = notesTextArea ? notesTextArea.value.trim() : '';

        if(!doctorNotes) {
            displayMessage(checkOutMessage, 'Doctor notes are required for check-out.', 'error');
            // Highlight the textarea or provide more specific feedback if needed
            if(notesTextArea) notesTextArea.style.borderColor = 'red';
            setTimeout(() => { if(notesTextArea) notesTextArea.style.borderColor = '#ddd'; }, 2000);
            return;
        }


        const visitIndex = visits.findIndex(v => v.visitId === visitId);
        if (visitIndex > -1) {
            visits[visitIndex].status = 'checked-out';
            visits[visitIndex].checkOutTime = new Date().toISOString();
            visits[visitIndex].doctorNotes = doctorNotes;
            saveVisits();
            displayMessage(checkOutMessage, `Patient checked out successfully.`, 'success');
            renderCheckedInPatients(); // Re-render the list
            renderDashboard();
        } else {
            displayMessage(checkOutMessage, 'Error: Visit not found.', 'error');
        }
    }

    // --- Dashboard ---
    function renderDashboard() {
        // 1. Currently Checked-In
        const currentlyCheckedIn = visits.filter(v => v.status === 'checked-in').length;
        currentlyCheckedInCountEl.textContent = currentlyCheckedIn;

        // 2. Total Visits Today
        const todayVisits = visits.filter(v => isToday(v.checkInTime)).length;
        totalVisitsTodayCountEl.textContent = todayVisits;

        // 3. Recent Check-Ins (Today)
        recentCheckInsListEl.innerHTML = '';
        const todayCheckIns = visits
            .filter(v => v.status === 'checked-in' && isToday(v.checkInTime))
            .sort((a, b) => new Date(b.checkInTime) - new Date(a.checkInTime)) // Newest first
            .slice(0, 5); // Show latest 5

        if (todayCheckIns.length === 0) {
            recentCheckInsListEl.innerHTML = '<li>No check-ins today.</li>';
        } else {
            todayCheckIns.forEach(visit => {
                const li = document.createElement('li');
                li.textContent = `${visit.patientName} - Reason: ${visit.reason} at ${formatDate(visit.checkInTime)}`;
                recentCheckInsListEl.appendChild(li);
            });
        }

        // 4. Recent Check-Outs (Today)
        recentCheckOutsListEl.innerHTML = '';
        const todayCheckOuts = visits
            .filter(v => v.status === 'checked-out' && isToday(v.checkOutTime))
            .sort((a, b) => new Date(b.checkOutTime) - new Date(a.checkOutTime)) // Newest first
            .slice(0, 5); // Show latest 5

        if (todayCheckOuts.length === 0) {
            recentCheckOutsListEl.innerHTML = '<li>No check-outs today.</li>';
        } else {
            todayCheckOuts.forEach(visit => {
                const li = document.createElement('li');
                li.textContent = `${visit.patientName} - Notes: ${visit.doctorNotes.substring(0,30)}... at ${formatDate(visit.checkOutTime)}`;
                recentCheckOutsListEl.appendChild(li);
            });
        }
    }


    // --- Navigation ---
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.dataset.section;
            showSection(sectionId);
            // Refresh dynamic content when navigating to certain sections
            if (sectionId === 'check-in') populatePatientSelect();
            if (sectionId === 'check-out') renderCheckedInPatients();
            if (sectionId === 'dashboard') renderDashboard();
        });
    });

    // --- Event Listeners Setup ---
    if (patientRegistrationForm) {
        patientRegistrationForm.addEventListener('submit', handlePatientRegistration);
    }
    if (checkInForm) {
        checkInForm.addEventListener('submit', handleCheckIn);
    }

    if(btnGoToRegister) {
        btnGoToRegister.addEventListener('click', () => {
            showSection('patient-registration');
        });
    }

    if(btnGoToCheckIn) {
        btnGoToCheckIn.addEventListener('click', () => {
            showSection('check-in');
            populatePatientSelect(); // Ensure patient list is fresh
        });
    }

    inlineNavLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.dataset.section;
            showSection(sectionId);
            // Pre-populate content if needed when using these links
            if (sectionId === 'check-in') populatePatientSelect();
            if (sectionId === 'check-out') renderCheckedInPatients();
            if (sectionId === 'dashboard') renderDashboard();
        });
    });

    // --- Initial Setup ---
    showSection('home'); // Show home section by default
    populatePatientSelect(); // Populate patient dropdown on load
    renderDashboard(); // Initial dashboard render
    renderCheckedInPatients(); // Initial render for checkout page
});