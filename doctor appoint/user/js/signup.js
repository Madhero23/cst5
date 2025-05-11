document.addEventListener('DOMContentLoaded', function() {
    // Show/hide doctor-specific fields based on role selection
    const doctorRadio = document.getElementById('doctor');
    const patientRadio = document.getElementById('patient');
    const doctorFields = document.querySelector('.doctor-fields');

    function toggleDoctorFields() {
        if (doctorRadio.checked) {
            doctorFields.style.display = 'block';
            // Make doctor-specific fields required
            document.getElementById('specialization').required = true;
            document.getElementById('licenseNumber').required = true;
            document.getElementById('consultationFee').required = true;
        } else {
            doctorFields.style.display = 'none';
            // Remove required attribute for patient signup
            document.getElementById('specialization').required = false;
            document.getElementById('licenseNumber').required = false;
            document.getElementById('consultationFee').required = false;
        }
    }

    doctorRadio.addEventListener('change', toggleDoctorFields);
    patientRadio.addEventListener('change', toggleDoctorFields);

    // Initialize fields on page load
    toggleDoctorFields();

    // Form validation
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            return false;
        }
        
        if (doctorRadio.checked) {
            const license = document.getElementById('licenseNumber').value;
            const specialization = document.getElementById('specialization').value;
            const fee = document.getElementById('consultationFee').value;
            
            if (!license) {
                e.preventDefault();
                alert('Please enter your license number');
                return false;
            }
            
            if (!specialization) {
                e.preventDefault();
                alert('Please enter your specialization');
                return false;
            }
            
            if (!fee || parseFloat(fee) < 0) {
                e.preventDefault();
                alert('Please enter a valid consultation fee');
                return false;
            }
        }
        
        return true;
    });
});