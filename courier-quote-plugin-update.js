document.addEventListener('DOMContentLoaded', function () {
    const fromAddressInput = document.getElementById("collectFrom");
    const toAddressInput = document.getElementById("deliverTo");
    const emailInput = document.getElementById("email");
    const getQuoteButton = document.getElementById("getQuote");
    const quoteResult = document.getElementById("quoteResult");
    const whenRadios = document.querySelectorAll('input[name="when"]');
    const vehicleRadios = document.querySelectorAll('input[name="vehicle"]');
    const customDateInput = document.getElementById("customDate");
    const timeDropdown = document.getElementById("timeSelection");

    let fromAutocomplete, toAutocomplete;

    // Initialize Google Maps Autocomplete
    function initialize() {
        fromAutocomplete = new google.maps.places.Autocomplete(fromAddressInput, { types: ["geocode"] });
        toAutocomplete = new google.maps.places.Autocomplete(toAddressInput, { types: ["geocode"] });
    }
    initialize();

	
    // Step navigation logic
    const steps = document.querySelectorAll(".step");
    let currentStep = 0;

    function showStep(stepIndex) {
        steps.forEach((step, index) => {
            step.classList.toggle("active", index === stepIndex);
            step.style.display = index === stepIndex ? "block" : "none";
        });
    }
    showStep(currentStep);


    document.querySelectorAll(".next-btn").forEach((button) => {
        button.addEventListener("click", () => {
            // 		alert("next");
            let errors = [];

            // Trim input values
            const fromAddress = fromAddressInput.value.trim();
            const toAddress = toAddressInput.value.trim();
            const email = emailInput.value.trim();
            const customDate = customDateInput.value.trim();
			const timeDropdownt = document.getElementById("timeSelection").value;

            // Validate required fields
            if (!customDate) errors.push("Please enter a pickup date.");
            if (!fromAddress) errors.push("Please enter a pickup address.");
            if (!toAddress) errors.push("Please enter a delivery address.");
			if (!timeDropdownt) errors.push("Please enter a delivery time.");
            if (!email) {
                errors.push("Please enter your email.");
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.push("Please enter a valid email.");
            }

            // Validate custom date if 'custom' is selected

            // Show errors if any
            if (errors.length > 0) {
                alert(errors.join("\n"));
                return;
            }

            // Show an alert with entered data
            let alertMessage = 'Entered Details:\n\nPickup Address: ${' + fromAddress + '}\nDelivery Address: ${' + toAddress + '}\nEmail:${' + email + '}\nCustom Date: ${' + customDate + '}';
            //if (selectedWhen === "custom") {
            //             alertMessage += '\nCustom Date: ${'+customDate+'}';
            //}

            //         alert(alertMessage);
            var ajaxurl = "https://rapidhaul.co.uk/wp-admin/admin-ajax.php";
            // 		email code
            fetch(ajaxurl, { // ajaxurl is available in WordPress
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: "send_booking_email",
                    fromAddress,
                    toAddress,
                    email,
                    customDate,
					timeDropdownt,
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Email sent successfully!");
                    } else {
                        console.log("Failed to send email.");
                    }
                }).catch(error => console.error("Error:", error));

            // 		email code end

            // Move to the next step if no errors
            if (currentStep < steps.length - 1) {
                if (currentStep === 0) {
                    calculateDistance(); // Only call this when moving from step 1 to step 2
                }
                currentStep++;
                showStep(currentStep);
            }
        });
    });



    document.querySelectorAll(".prev-btn").forEach((button) => {
        button.addEventListener("click", () => {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    });
    document.querySelectorAll(".prev-btnn").forEach((button) => {
        button.addEventListener("click", () => {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    });

    function getCurrentTimeRange() {
        const now = new Date();
        let currentHour = now.getHours();
        let nextHour = (currentHour + 1) % 24;
        return `${String(currentHour).padStart(2, '0')}:00 - ${String(nextHour).padStart(2, '0')}:00`;
    }
  

    // Date input toggle
    whenRadios.forEach(input => {
        input.addEventListener("change", function () {
			const tomorrowValue =document.getElementById("to").value;
    const nextWeekValue = document.getElementById("nw").value;
            if (this.value === "custom") {
// 				this.value = customDateInput.value.trim();
                customDateInput.disabled = false;
                timeDropdown.disabled = false;
            } else if (this.value === tomorrowValue || this.value === nextWeekValue) {
                customDateInput.disabled = true;
                timeDropdown.disabled = false;
            } else { // If "Now" is selected
                customDateInput.disabled = true;
                timeDropdown.disabled = true;
                timeDropdown.value = getCurrentTimeRange(); // Set current time range
            }
        });
    });

    function calculateDistance() {
        const fromAddress = fromAddressInput.value.trim();
        const toAddress = toAddressInput.value.trim();
        const customDate = customDateInput.value.trim();
        if (!fromAddress || !toAddress) {
            alert("Please enter both addresses to calculate distance.");
            return;
        }

        // Use Google Maps Distance Matrix API
        const service = new google.maps.DistanceMatrixService();
        service.getDistanceMatrix({
            origins: [fromAddress],
            destinations: [toAddress],
            travelMode: "DRIVING",
            drivingOptions: {
                departureTime: new Date(Date.now()),  // for the time N milliseconds from now.
                trafficModel: 'bestguess'
            }
        }, (response, status) => {
            if (status === "OK" && response.rows[0].elements[0].status === "OK") {
                // Distance calculations
                const distanceInKm = response.rows[0].elements[0].distance.value / 1000; // Convert meters to km
                const distanceInMiles = distanceInKm * 0.621371; // Convert km to miles

                // Duration calculations
                const durationText = response.rows[0].elements[0].duration.text; // e.g., "1 hour 5 mins"
                const durationSeconds = response.rows[0].elements[0].duration.value; // Duration in seconds
                const durationMinutes = durationSeconds / 60; // Convert seconds to minutes
				
				
				console.log("distance: M " + distanceInMiles);
				console.log("Total Cost: £" + calculateCost(distanceInMiles, durationMinutes));

                // Get settings values from the localized object
                const costPerMile = parseFloat(cq_settings.costPerMile) || 1.3;
                const additionalCost = parseFloat(cq_settings.additionalCost) || 50;
                const mediumMultiplier = parseFloat(cq_settings.mediumVanMultiplier) || 1.2;
                const largeMultiplier = parseFloat(cq_settings.largeVanMultiplier) || 1.3;
                const xLargeMultiplier = parseFloat(cq_settings.xLargeVanMultiplier) || 1.3;
                const lutonMultiplier = parseFloat(cq_settings.lutonVanMultiplier) || 1.6;

				
				
				
//                 // Calculate base cost ($costPerMile per mile)
//                 var baseCost = distanceInMiles * costPerMile;
//                 if (baseCost <= 30) {
//                     baseCost = 30;
//                 }

//                 // Calculate additional cost for time ($additionalCost per hour)
//                 const durationHours = durationMinutes / 60;
//                 const additionalTimeCost = durationHours * additionalCost;

                // Total cost is the sum of base cost and additional time cost
//                 const totalCost = baseCost + additionalTimeCost;
                var totalCost = calculateCost(distanceInMiles, durationMinutes);
totalCost = parseFloat(totalCost);
                // Van prices calculated based on total cost using the multipliers from admin
                var smallVanPrice = totalCost;
                var mediumVanPrice = totalCost * mediumMultiplier;
                var largeVanPrice = totalCost * largeMultiplier;
                var xLargeVanPrice = totalCost * xLargeMultiplier;
                var lutonVanPrice = totalCost * lutonMultiplier;

                updateVehiclePrice("smallVan", smallVanPrice);
                updateVehiclePrice("mediumVan", mediumVanPrice);
                updateVehiclePrice("largeVan", largeVanPrice);
                updateVehiclePrice("xlargeVan", xLargeVanPrice);
                updateVehiclePrice("lutonVan", lutonVanPrice);

                var pickuploc = document.getElementById("pickuploc");
                var deliverloc = document.getElementById("deliverloc");
                var dleviverdtae = document.getElementById("dleviverdtae");
                var esttime = document.getElementById("esttime");

                const today = new Date().toISOString().split('T')[0];


                const now = new Date();
                let hours = now.getHours();
                const minutes = now.getMinutes();
                const seconds = now.getSeconds();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12; // Convert 0 to 12 for 12 AM

                const time = `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')} ${ampm}`;
                const updatedTime = addDuration(time, durationText);

                pickuploc.textContent = fromAddress;
                deliverloc.textContent = toAddress;
                esttime.textContent = durationText;
                if (customDate == today) {
                    dleviverdtae.textContent = customDate +" - "+ updatedTime;
                } else {
                    dleviverdtae.textContent = customDate +" - "+timeDropdown.value;
                }




                //             Display the results: now showing distance in miles
//                             quoteResult.textContent = `Distance: ${distanceInMiles.toFixed(2)} miles km ${distanceInKm}, 
//                 Estimated Time: ${durationText} (${Math.round(durationMinutes)} minutes), 
//                 Estimated Cost: £${totalCost.toFixed(2)} `;
            } else {
                alert("Error calculating distance. Please check addresses.");
            }
        });
    }
    // Function to parse duration text and add time
    function addDuration(timeString, durationString) {
        const durationParts = durationString.match(/(\d+)\s*hour[s]?|(\d+)\s*min[s]?/g) || [];

        let addedHours = 0;
        let addedMinutes = 0;

        durationParts.forEach(part => {
            if (part.includes("hour")) {
                addedHours += parseInt(part);
            } else if (part.includes("min")) {
                addedMinutes += parseInt(part);
            }
        });
         const now = new Date();
        // Create new date object and add time
        const updatedTime = new Date(now);
        updatedTime.setHours(now.getHours() + addedHours);
        updatedTime.setMinutes(now.getMinutes() + addedMinutes);

        // Format new time
        let newHours = updatedTime.getHours();
        const newMinutes = updatedTime.getMinutes();
        const newSeconds = updatedTime.getSeconds();
        const newAmpm = newHours >= 12 ? 'PM' : 'AM';
        newHours = newHours % 12 || 12; // Convert 0 to 12 for 12 AM

        return `${newHours}:${newMinutes.toString().padStart(2, '0')}:${newSeconds.toString().padStart(2, '0')} ${newAmpm}`;
    }



    // Get Quote handler
    getQuoteButton.addEventListener("click", function () {
        const email = emailInput.value.trim();
        const selectedWhen = Array.from(whenRadios).find(radio => radio.checked)?.value;
        const selectedVehicle = Array.from(vehicleRadios).find(radio => radio.checked)?.value;
        const customDate = selectedWhen === 'custom' ? customDateInput.value.trim() : null;

        // Validation
        let errors = [];
        if (!selectedWhen) errors.push("Please select a collection time");
        if (selectedWhen === 'custom' && !customDate) errors.push("Custom date is required");
        if (!selectedVehicle) errors.push("Vehicle selection is required");
        if (!email) errors.push("Email is required");
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push("Invalid email format");

        if (errors.length > 0) {
            alert(errors.join("\n"));
            return;
        }

        // alert("Quote request submitted!");
    });


    function updateVehiclePrice(vehicleId, price) {
		console.log("Price: "+vehicleId+"-"+price);
		price = parseFloat(price);
		const tax= (price*20)/100;
		const taxwithprice = ((price*20)/100)+price;
		console.log("taxprice"+ taxwithprice);
        const vehicleElement = document.getElementById(vehicleId);
        if (vehicleElement) {
            vehicleElement.setAttribute("data-price", taxwithprice.toFixed(2)); // Update data-price attribute


			 const pricewithtax = vehicleElement.closest("label").querySelector(".pricewithtax");
            // Find the corresponding p.price element inside the label
            const priceElement = vehicleElement.closest("label").querySelector(".price");
            if (priceElement) {
                priceElement.textContent = "£" + taxwithprice.toFixed(2);
            }if (pricewithtax) {
                pricewithtax.textContent = "£" + price.toFixed(2) +" + "+ tax.toFixed(2);
            }
        }
    }


    $('input[name="when"]').change(function () {
        if ($(this).val() === "custom") {
            $('#customDate').prop('disabled', false).focus();
        } else {
            $('#customDate').prop('disabled', true).val($(this).val());
        }
    });

    const vehicleOptions = document.querySelectorAll(".vehicle-option");

    vehicleOptions.forEach(option => {
        option.addEventListener("click", function () {
            // Remove the 'selected' class from all options
            vehicleOptions.forEach(opt => opt.classList.remove("selected"));

            // Add the 'selected' class to the clicked option
            this.classList.add("selected");

            // Select the radio button inside the clicked option
            this.querySelector("input[type='radio']").checked = true;
        });
    });

});
function calculateCost(distanceInMiles, durationMinutes) {
    const collectionFee = 25;
    const firstHourRate = 40;
    const subsequentRatePerMinute = 0.1666; // £10 per hour = £0.1666 per minute
    const perMileRate = 0.60;
    
    // Driving cost calculation
    let drivingCost = 0;
    if (durationMinutes <= 60) {
        drivingCost = firstHourRate;
    } else {
        drivingCost = firstHourRate + ((durationMinutes - 60) * subsequentRatePerMinute);
    }
    
    // Distance cost calculation
    const distanceCost = distanceInMiles * perMileRate;
    
    // Total cost
    const totalCost = collectionFee + drivingCost + distanceCost;
     
    return totalCost.toFixed(2); // Return the total cost rounded to 2 decimal places
}

jQuery(document).on('click', '.vehicle-option', function() {
    jQuery('button#getQuote').trigger('click');
});