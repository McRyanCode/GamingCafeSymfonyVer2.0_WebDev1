document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("bookingForm");
    const selectionStep = document.getElementById("selectionStep");
    const reviewStep = document.getElementById("reviewStep");
    const stationInput = document.getElementById("stationInput");
    const bookingDateInput = document.getElementById("bookingDate");
    const bookingTimeInput = document.getElementById("bookingTime");
    const hoursInput = document.getElementById("hoursInput");
    const priceDisplay = document.getElementById("priceDisplay");
    const tierDisplay = document.getElementById("tierDisplay");
    const selectionError = document.getElementById("selectionError");
    const paymentMethod = document.getElementById("paymentMethod");
    const paymentNote = document.getElementById("paymentNote");
    const confirmButton = document.getElementById("confirmBookingBtn");
    const reviewButton = document.getElementById("reviewBookingBtn");
    const backButton = document.getElementById("backToEditBtn");
    const stationCards = document.querySelectorAll(".device-card[data-station-id]");
    const durationPresets = document.querySelectorAll(".duration-preset");

    if (!form || !stationInput || !bookingDateInput || !bookingTimeInput || !hoursInput) {
        return;
    }

    let selectedStation = null;

    function formatPrice(amount) {
        return `₱${amount.toLocaleString("en-PH")}`;
    }

    function getPricing() {
        if (!selectedStation) {
            return null;
        }

        const hours = Number(hoursInput.value);
        const isConsole = selectedStation.type.toLowerCase().includes("console");
        const hourlyRate = isConsole ? 30 : 40;

        if (hours <= 2) {
            return { tier: "BASIC", total: hours * hourlyRate };
        }

        if (hours <= 4) {
            return {
                tier: "STANDARD",
                total: (isConsole ? 80 : 100) + ((hours - 3) * hourlyRate)
            };
        }

        return {
            tier: "PREMIUM",
            total: (isConsole ? 120 : 150) + ((hours - 5) * hourlyRate)
        };
    }

    function updatePrice() {
        const pricing = getPricing();
        if (tierDisplay) {
            tierDisplay.textContent = pricing ? pricing.tier : "Choose a station";
        }
        if (priceDisplay) {
            priceDisplay.textContent = pricing ? formatPrice(pricing.total) : "Select a station";
        }
        durationPresets.forEach((preset) => {
            preset.classList.toggle("is-active", Number(preset.dataset.hours) === Number(hoursInput.value));
        });
    }

    function selectStation(card) {
        selectedStation = {
            id: card.dataset.stationId,
            name: card.dataset.stationName,
            type: card.dataset.stationType
        };
        stationInput.value = selectedStation.id;
        stationCards.forEach((stationCard) => {
            const isSelected = stationCard === card;
            stationCard.classList.toggle("selected", isSelected);
            stationCard.setAttribute("aria-pressed", String(isSelected));
            const label = stationCard.querySelector(".select-btn");
            if (label) {
                label.textContent = isSelected ? "Selected" : "Select station";
            }
        });
        if (selectionError) {
            selectionError.textContent = "";
        }
        updatePrice();
    }

    stationCards.forEach((card) => {
        card.addEventListener("click", () => selectStation(card));
    });

    const previouslySelectedCard = Array.from(stationCards).find((card) => card.dataset.stationId === stationInput.value);
    if (previouslySelectedCard) {
        selectStation(previouslySelectedCard);
    }

    function setHours(value) {
        const hours = Math.min(12, Math.max(1, Number(value) || 1));
        hoursInput.value = String(hours);
        updatePrice();
    }

    document.getElementById("minusBtn")?.addEventListener("click", () => setHours(Number(hoursInput.value) - 1));
    document.getElementById("plusBtn")?.addEventListener("click", () => setHours(Number(hoursInput.value) + 1));
    hoursInput.addEventListener("input", () => setHours(hoursInput.value));
    durationPresets.forEach((preset) => {
        preset.addEventListener("click", () => setHours(preset.dataset.hours));
    });

    function displayDate(value) {
        const [year, month, day] = value.split("-").map(Number);
        return new Date(year, month - 1, day).toLocaleDateString(undefined, {
            weekday: "short",
            month: "short",
            day: "numeric",
            year: "numeric"
        });
    }

    function displayTime(value) {
        const [hour, minute] = value.split(":").map(Number);
        return new Date(2000, 0, 1, hour, minute).toLocaleTimeString(undefined, {
            hour: "numeric",
            minute: "2-digit"
        });
    }

    reviewButton?.addEventListener("click", () => {
        if (!selectedStation) {
            selectionError.textContent = "Choose a station before continuing.";
            return;
        }

        selectionError.textContent = "";
        if (!bookingDateInput.reportValidity() || !bookingTimeInput.reportValidity() || !hoursInput.reportValidity()) {
            return;
        }

        const pricing = getPricing();
        document.getElementById("sumStation").textContent = selectedStation.name;
        document.getElementById("sumType").textContent = selectedStation.type;
        document.getElementById("sumDate").textContent = displayDate(bookingDateInput.value);
        document.getElementById("sumTime").textContent = displayTime(bookingTimeInput.value);
        document.getElementById("sumDuration").textContent = `${hoursInput.value} hour${Number(hoursInput.value) === 1 ? "" : "s"}`;
        document.getElementById("sumTier").textContent = pricing.tier;
        document.getElementById("sumPrice").textContent = formatPrice(pricing.total);

        selectionStep.hidden = true;
        reviewStep.hidden = false;
        reviewStep.scrollIntoView({ behavior: "smooth", block: "start" });
        updatePaymentMessage();
    });

    backButton?.addEventListener("click", () => {
        reviewStep.hidden = true;
        selectionStep.hidden = false;
        selectionStep.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    function updatePaymentMessage() {
        if (!paymentMethod || !paymentNote || !confirmButton) {
            return;
        }

        if (paymentMethod.value === "Cash at Counter") {
            paymentNote.textContent = "Your station will be reserved. Pay the cafe staff when you arrive.";
            confirmButton.textContent = "Confirm booking — pay at counter";
        } else {
            paymentNote.textContent = "Demo checkout only: no money will be charged. Your receipt will clearly say this is a demo payment.";
            confirmButton.textContent = "Complete demo payment";
        }
    }

    paymentMethod?.addEventListener("change", updatePaymentMessage);
    updatePaymentMessage();

    form.addEventListener("submit", (event) => {
        if (reviewStep.hidden) {
            event.preventDefault();
            reviewButton?.click();
            return;
        }

        if (confirmButton) {
            confirmButton.disabled = true;
            confirmButton.textContent = "Saving your booking…";
        }
    });

    updatePrice();
});
