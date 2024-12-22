const AppointmentBooking = () => {
  const [appointmentDetails, setAppointmentDetails] = useState({
    patient_name: '',
    patient_email: '',
    appointment_date: '',
    appointment_time: ''
  });

  // Make sure this function is defined
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setAppointmentDetails(prev => ({
      ...prev,
      [name]: value
    }));
  };

  return (
    <form>
      <input
        type="text"
        name="patient_name"
        value={appointmentDetails.patient_name}
        onChange={handleInputChange}  
      />
      {/* Other inputs... */}
    </form>
  );
};