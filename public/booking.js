document.addEventListener("DOMContentLoaded", () => {
    // Check if a completed online payment booking exists to show receipt view immediately
    const completedBookingData = sessionStorage.getItem("completed_booking");
    if (completedBookingData) {
        const data = JSON.parse(completedBookingData);
        sessionStorage.removeItem("completed_booking");

        const selectionStep = document.getElementById("selectionStep");
        const summaryStep = document.getElementById("summaryStep");
        const successStep = document.getElementById("successStep");
        const selectionBar = document.getElementById("selectionBar");

        if (selectionStep) selectionStep.style.display = "none";
        if (summaryStep) summaryStep.style.display = "none";
        if (selectionBar) selectionBar.style.display = "none";
        if (successStep) successStep.style.display = "block";

        const succBookingId = document.getElementById("succBookingId");
        const succDevice = document.getElementById("succDevice");
        const succDateTime = document.getElementById("succDateTime");
        const succDuration = document.getElementById("succDuration");
        const succPrice = document.getElementById("succPrice");
        const succPaymentMethod = document.getElementById("succPaymentMethod");
        const succPaymentStatus = document.getElementById("succPaymentStatus");

        if (succBookingId) succBookingId.textContent = `#BOOK-${String(data.booking_id).padStart(5, '0')}`;
        if (succDevice) succDevice.textContent = `${data.device_id} (${data.device_type})`;
        if (succDateTime) succDateTime.textContent = `${data.booking_date} at ${data.start_time}`;
        if (succDuration) succDuration.textContent = data.duration;
        if (succPrice) succPrice.textContent = data.total_price;
        if (succPaymentMethod) succPaymentMethod.textContent = data.payment_method;
        if (succPaymentStatus) {
            succPaymentStatus.textContent = data.payment_status;
            succPaymentStatus.style.color = data.payment_status === 'Pending' ? '#ffaa00' : '#00f5d4';
        }
        return; 
    }

    // State Tracking
    let selectedDevice = null;
    let timeMode = "Fixed"; // "Fixed" or "Open"
    let hours = 1;
    let selectedDate = "";
    let selectedTime = "";

    // Set Default Today's Date
    const today = new Date().toISOString().split('T')[0];
    const bookingDateInput = document.getElementById("bookingDate");
    if (bookingDateInput) {
        bookingDateInput.value = today;
        bookingDateInput.min = today;
        selectedDate = today;
    }

    // Set Default Current Time
    const bookingTimeInput = document.getElementById("bookingTime");
    if (bookingTimeInput) {
        const now = new Date();
        const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
        bookingTimeInput.value = currentTime;
        selectedTime = currentTime;
    }

    // UI Views
    const selectionStep = document.getElementById("selectionStep");
    const summaryStep = document.getElementById("summaryStep");
    const successStep = document.getElementById("successStep");
    const selectionBar = document.getElementById("selectionBar");

    // UI Elements
    const deviceCards = document.querySelectorAll(".device-card");
    const selectedDeviceText = document.getElementById("selectedDeviceText");
    const selectedTimeText = document.getElementById("selectedTimeText");
    const barPriceText = document.getElementById("barPriceText");

    const timeModeRadios = document.querySelectorAll("input[name='timeMode']");
    const fixedHoursWrapper = document.getElementById("fixedHoursWrapper");
    const openInfoWrapper = document.getElementById("openInfoWrapper");

    const hoursInput = document.getElementById("hoursInput");
    const minusBtn = document.getElementById("minusBtn");
    const plusBtn = document.getElementById("plusBtn");

    const tierDisplay = document.getElementById("tierDisplay");
    const priceDisplay = document.getElementById("priceDisplay");

    // UI Buttons
    const reviewBookingBtn = document.getElementById("reviewBookingBtn");
    const backToEditBtn = document.getElementById("backToEditBtn");
    const confirmBookingBtn = document.getElementById("confirmBookingBtn");

    // Summary Display Elements
    const sumDevice = document.getElementById("sumDevice");
    const sumType = document.getElementById("sumType");
    const sumDate = document.getElementById("sumDate");
    const sumTime = document.getElementById("sumTime");
    const sumDuration = document.getElementById("sumDuration");
    const sumTier = document.getElementById("sumTier");
    const sumPrice = document.getElementById("sumPrice");

    // Success Display Elements
    const succBookingId = document.getElementById("succBookingId");
    const succDevice = document.getElementById("succDevice");
    const succDateTime = document.getElementById("succDateTime");
    const succDuration = document.getElementById("succDuration");
    const succPrice = document.getElementById("succPrice");

    const succPaymentMethod = document.getElementById("succPaymentMethod");
    const succPaymentStatus = document.getElementById("succPaymentStatus");
    const paymentMethodSelect = document.getElementById("payment_method");

    // Payment Dropdown Change Listener (Dynamic Button text)
    if (paymentMethodSelect && confirmBookingBtn) {
        paymentMethodSelect.addEventListener("change", (e) => {
            const selectedMethod = e.target.value;
            
            if (selectedMethod === "GCash" || selectedMethod === "PayMaya") {
                confirmBookingBtn.textContent = `Pay via ${selectedMethod}`;
                confirmBookingBtn.style.background = "#00f5d4"; 
                confirmBookingBtn.style.color = "#0f111a";
            } else {
                confirmBookingBtn.textContent = "Confirm Booking";
                confirmBookingBtn.style.background = ""; 
                confirmBookingBtn.style.color = "";
            }
        });
    }

    // 1. Calculation Function
    function calculatePricing() {
        const isConsole = selectedDevice && selectedDevice.type === "Console";
        const baseRate = isConsole ? 30 : 40;
        const standardBase = isConsole ? 80 : 100;
        const premiumBase = isConsole ? 120 : 150;

        if (timeMode === "Open") {
            return {
                tier: "OPEN TIME",
                price: baseRate,
                displayText: `₱${baseRate} (Min Deposit)`
            };
        }

        if (hours <= 2) {
            const total = hours * baseRate;
            return { tier: "BASIC", price: total, displayText: `₱${total}` };
        } else if (hours <= 4) {
            const extraHours = hours - 3;
            const total = standardBase + (extraHours * baseRate);
            return { tier: "STANDARD", price: total, displayText: `₱${total}` };
        } else {
            const extraHours = hours - 5;
            const total = premiumBase + (extraHours * baseRate);
            return { tier: "PREMIUM", price: total, displayText: `₱${total}` };
        }
    }

    // Date/Time Events
    if (bookingDateInput) bookingDateInput.addEventListener("change", (e) => selectedDate = e.target.value);
    if (bookingTimeInput) bookingTimeInput.addEventListener("change", (e) => selectedTime = e.target.value);

    // Device Cards Selection
    deviceCards.forEach(card => {
        card.addEventListener("click", () => {
            deviceCards.forEach(c => {
                c.classList.remove("selected");
                const btn = c.querySelector(".select-btn");
                if (btn) btn.textContent = `Select ${c.dataset.deviceName}`;
            });

            card.classList.add("selected");
            const btn = card.querySelector(".select-btn");
            if (btn) btn.textContent = "Selected";

            selectedDevice = {
                id: card.dataset.deviceId,
                type: card.dataset.deviceType,
                name: card.dataset.deviceName
            };

            updateSummary();
        });
    });

    // Time Mode Selection
    timeModeRadios.forEach(radio => {
        radio.addEventListener("change", (e) => {
            timeMode = e.target.value;
            if (timeMode === "Open") {
                fixedHoursWrapper.style.display = "none";
                openInfoWrapper.style.display = "block";
            } else {
                fixedHoursWrapper.style.display = "inline-block";
                openInfoWrapper.style.display = "none";
            }
            updateSummary();
        });
    });

    // Hour Counter Buttons
    if (plusBtn) plusBtn.addEventListener("click", () => { hours++; hoursInput.value = hours; updateSummary(); });
    if (minusBtn) minusBtn.addEventListener("click", () => { if (hours > 1) { hours--; hoursInput.value = hours; updateSummary(); } });
    if (hoursInput) {
        hoursInput.addEventListener("input", (e) => {
            let val = parseInt(e.target.value, 10);
            if (isNaN(val) || val < 1) val = 1;
            hours = val;
            hoursInput.value = hours;
            updateSummary();
        });
    }

    function updateSummary() {
        const pricing = calculatePricing();
        if (selectedDeviceText) selectedDeviceText.textContent = selectedDevice ? `${selectedDevice.name} (${selectedDevice.type})` : "None";
        if (selectedTimeText) selectedTimeText.textContent = timeMode === "Open" ? "Open Time" : `${hours} Hour(s) (Fixed)`;
        if (tierDisplay) tierDisplay.textContent = pricing.tier;
        if (priceDisplay) priceDisplay.textContent = pricing.displayText;
        if (barPriceText) barPriceText.textContent = pricing.displayText;
        if (minusBtn) minusBtn.disabled = (hours <= 1);
    }

    // Review Button Click
    if (reviewBookingBtn) {
        reviewBookingBtn.addEventListener("click", () => {
            if (!selectedDevice) return alert("Please select a PC or Console station before proceeding.");
            if (!selectedDate) return alert("Please select a valid booking date.");
            if (!selectedTime) return alert("Please select a valid start time.");

            const pricing = calculatePricing();
            if (sumDevice) sumDevice.textContent = selectedDevice.name;
            if (sumType) sumType.textContent = selectedDevice.type;
            if (sumDate) sumDate.textContent = selectedDate;
            if (sumTime) sumTime.textContent = selectedTime;
            if (sumDuration) sumDuration.textContent = timeMode === "Open" ? "Open Time" : `${hours} Hour(s) (Fixed)`;
            if (sumTier) sumTier.textContent = pricing.tier;
            if (sumPrice) sumPrice.textContent = pricing.displayText;

            if (selectionStep) selectionStep.style.display = "none";
            if (selectionBar) selectionBar.style.display = "none";
            if (summaryStep) summaryStep.style.display = "block";
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Back Button Click
    if (backToEditBtn) {
        backToEditBtn.addEventListener("click", () => {
            if (summaryStep) summaryStep.style.display = "none";
            if (selectionStep) selectionStep.style.display = "block";
            
            if (selectionBar) {
                selectionBar.style.display = "flex";
            }

            if (confirmBookingBtn) {
                confirmBookingBtn.disabled = false;
                confirmBookingBtn.textContent = "Confirm Booking";
                confirmBookingBtn.style.background = ""; 
                confirmBookingBtn.style.color = "";
            }
        });
    }

    // Confirm / Pay Button Click
    if (confirmBookingBtn) {
        confirmBookingBtn.addEventListener("click", () => {
            const paymentMethodVal = paymentMethodSelect ? paymentMethodSelect.value : "Cash at Counter";
            const totalPrice = priceDisplay ? priceDisplay.textContent : "₱0";
            const deviceName = selectedDevice ? selectedDevice.name : "Station";

            const payload = {
                device_id: selectedDevice ? selectedDevice.id : "",
                device_type: selectedDevice ? selectedDevice.type : "",
                booking_date: bookingDateInput ? bookingDateInput.value : selectedDate,
                start_time: bookingTimeInput ? bookingTimeInput.value : selectedTime,
                time_mode: timeMode,
                hours: parseInt(hours, 10) || 1,
                payment_method: paymentMethodVal,
                payment_status: paymentMethodVal === "Cash at Counter" ? "Pending" : "Paid"
            };

            // Redirect to simulation pages if GCash or PayMaya
            if (paymentMethodVal === "GCash") {
                sessionStorage.setItem("pending_booking", JSON.stringify(payload));
                window.location.href = `gcash_payment.php?amount=${encodeURIComponent(totalPrice)}&device=${encodeURIComponent(deviceName)}`;
                return;
            } else if (paymentMethodVal === "PayMaya") {
                sessionStorage.setItem("pending_booking", JSON.stringify(payload));
                window.location.href = `paymaya_payment.php?amount=${encodeURIComponent(totalPrice)}&device=${encodeURIComponent(deviceName)}`;
                return;
            }

            // Normal AJAX submission for Cash at Counter
            confirmBookingBtn.disabled = true;
            confirmBookingBtn.textContent = "Saving Booking...";

            fetch("/booking/process", {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (succBookingId) succBookingId.textContent = `#BOOK-${String(data.booking_id).padStart(5, '0')}`;
                    if (succDevice) succDevice.textContent = `${data.device_id} (${data.device_type})`;
                    if (succDateTime) succDateTime.textContent = `${data.booking_date} at ${data.start_time}`;
                    if (succDuration) succDuration.textContent = data.duration;
                    if (succPrice) succPrice.textContent = data.total_price;
                    if (succPaymentMethod) succPaymentMethod.textContent = data.payment_method;
                    if (succPaymentStatus) {
                        succPaymentStatus.textContent = data.payment_status;
                        succPaymentStatus.style.color = data.payment_status === 'Pending' ? '#ffaa00' : '#00f5d4';
                    }
                    if (summaryStep) summaryStep.style.display = "none";
                    if (successStep) successStep.style.display = "block";
                } else {
                    alert(data.message);
                    confirmBookingBtn.disabled = false;
                    confirmBookingBtn.textContent = "Confirm Booking";
                }
            })  
            .catch(error => {
                console.error("Booking Error:", error);
                alert("Network or Parsing Error: " + error.message);
                confirmBookingBtn.disabled = false;
                confirmBookingBtn.textContent = "Confirm Booking";
            });
        });
    }
});

// Station Availability Checker Function
function updateStationAvailability() {
    const dateInput = document.getElementById('bookingDate');
    const timeInput = document.getElementById('startTime');
    
    if (!dateInput || !timeInput) return;
    const selectedDate = dateInput.value;
    const selectedTime = timeInput.value;

    if (!selectedDate || !selectedTime) return;

    fetch(`check_availability.php?date=${selectedDate}&time=${selectedTime}`)
        .then(res => res.json())
        .then(data => {
            const occupiedList = data.occupied_stations || [];

            document.querySelectorAll('.station-card').forEach(card => {
                const stationId = card.dataset.stationId;
                const badge = card.querySelector('.status-badge');
                const selectBtn = card.querySelector('.btn-select-station');

                if (occupiedList.includes(stationId)) {
                    if (badge) {
                        badge.textContent = 'OCCUPIED';
                        badge.className = 'status-badge occupied';
                    }
                    card.classList.add('is-occupied');
                    if (selectBtn) {
                        selectBtn.disabled = true;
                        selectBtn.textContent = 'Unavailable';
                    }
                } else {
                    if (badge) {
                        badge.textContent = 'AVAILABLE';
                        badge.className = 'status-badge available';
                    }
                    card.classList.remove('is-occupied');
                    if (selectBtn) {
                        selectBtn.disabled = false;
                        selectBtn.textContent = `Select ${stationId}`;
                    }
                }
            });
        })
        .catch(err => console.error('Error updating availability:', err));
}

// Trigger update when inputs change (safeguarded)
const globalDateInput = document.getElementById('bookingDate');
const globalStartTimeInput = document.getElementById('startTime');
if (globalDateInput) globalDateInput.addEventListener('change', updateStationAvailability);
if (globalStartTimeInput) globalStartTimeInput.addEventListener('change', updateStationAvailability);