(function () {
    const dashboard = document.querySelector("[data-live-dashboard]");

    if (!dashboard) {
        return;
    }

    const apiUrl = "api/get_latest_reading.php";
    const pollMs = 5000;

    const setText = (selector, value) => {
        document.querySelectorAll(selector).forEach((element) => {
            element.textContent = value;
        });
    };

    const updateDashboardClock = () => {
        const now = new Date();
        const hour = now.getHours();
        const greeting = hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening";
        const overview = hour < 12
            ? "Morning field overview"
            : hour < 18 ? "Afternoon field overview" : "Evening field overview";

        setText("[data-dashboard-greeting]", greeting);
        setText("[data-greeting-period]", overview);
        setText("[data-dashboard-date]", now.toLocaleDateString("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric"
        }));
        setText("[data-dashboard-time]", now.toLocaleTimeString("en-US", {
            hour: "numeric",
            minute: "2-digit",
            second: "2-digit"
        }));
    };

    const setSystemStatus = (status, label) => {
        const pill = document.querySelector("[data-system-pill]");
        const labels = document.querySelectorAll("[data-system-label]");
        const heroStatus = document.querySelector("[data-hero-status]");
        const heroStatusLabels = document.querySelectorAll("[data-hero-status-label]");
        const commandStatus = document.querySelector("[data-command-status]");
        const commandStatusLabel = document.querySelector("[data-command-status-label]");
        const fieldMood = document.querySelector("[data-field-mood]");
        const fieldPulseDots = document.querySelectorAll(".field-pulse-live-dot");

        labels.forEach((text) => {
            text.textContent = label;
        });

        if (pill) {
            pill.classList.toggle("is-offline", status === "offline");
            pill.classList.toggle("is-stale", status === "stale");
        }

        if (heroStatus) {
            heroStatus.classList.toggle("is-live", status === "live");
            heroStatus.classList.toggle("is-offline", status !== "live");
            heroStatus.classList.toggle("is-stale", status === "stale");
        }

        heroStatusLabels.forEach((text) => {
            text.textContent = status === "live" ? "Sensor Live" : label;
        });

        if (commandStatus) {
            commandStatus.classList.toggle("is-online", status === "live");
            commandStatus.classList.toggle("is-offline", status !== "live");
        }

        if (commandStatusLabel) {
            commandStatusLabel.textContent = status === "live" ? "Online" : (status === "stale" ? "Stale" : "Offline");
        }

        if (fieldMood) {
            fieldMood.textContent = status === "live"
                ? "Ready for today's scan"
                : (status === "stale" ? "Waiting for a fresh reading" : "Waiting for sensor connection");
        }

        fieldPulseDots.forEach((dot) => {
            dot.classList.toggle("is-live", status === "live");
            dot.classList.toggle("is-stale", status === "stale");
        });

        setText("[data-device-online-count]", status === "live" ? "1" : "0");
    };

    const numberValue = (value) => {
        if (value === null || value === undefined || value === "") {
            return null;
        }

        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    };

    const fixed = (value, digits = 1) => {
        const parsed = numberValue(value);
        return parsed === null ? "--" : parsed.toFixed(digits);
    };

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

    const rangeScore = (value, min, max) => {
        const parsed = numberValue(value);

        if (parsed === null) {
            return 0;
        }

        if (parsed >= min && parsed <= max) {
            return 100;
        }

        const distance = parsed < min ? min - parsed : parsed - max;
        const tolerance = Math.max(1, (max - min) * 0.7);

        return clamp(Math.round(100 - (distance / tolerance) * 100), 0, 100);
    };

    const percentText = (value) => `${fixed(value, 0)}%`;
    const celsiusText = (value) => `${fixed(value, 1)} C`;
    const nutrientText = (value) => fixed(value, 0);

    const relativeTime = (ageSeconds) => {
        const age = numberValue(ageSeconds);

        if (age === null) {
            return "just now";
        }

        if (age < 60) {
            return "just now";
        }

        if (age < 3600) {
            return `${Math.floor(age / 60)} min ago`;
        }

        return `${Math.floor(age / 3600)} hr ago`;
    };

    const setBar = (name, score, displayValue = null) => {
        const value = clamp(score, 0, 100);
        const label = displayValue === null ? `${value}%` : displayValue;

        document.querySelectorAll(`[data-readiness-bar="${name}"]`).forEach((bar) => {
            bar.style.setProperty("--readiness-width", `${value}%`);
            bar.style.setProperty("--readiness-height", `${value}%`);
            bar.setAttribute("aria-label", `${name}: ${label}`);
        });

        document.querySelectorAll(`[data-readiness-value="${name}"]`).forEach((label) => {
            label.textContent = displayValue === null ? `${value}%` : displayValue;
        });
    };

    const updateCropCards = (reading = null) => {
        const soilPh = reading ? numberValue(reading.ph ?? reading.soil_ph) : null;
        const temperature = reading ? numberValue(reading.temperature) : null;

        document.querySelectorAll("[data-crop-card]").forEach((card) => {
            const status = card.querySelector("[data-crop-status]");
            const note = card.querySelector("[data-crop-note]");
            const phMin = numberValue(card.dataset.phMin);
            const phMax = numberValue(card.dataset.phMax);
            const temperatureMin = numberValue(card.dataset.temperatureMin);
            const temperatureMax = numberValue(card.dataset.temperatureMax);
            const phTolerance = numberValue(card.dataset.phTolerance) ?? 0.2;
            const temperatureTolerance = numberValue(card.dataset.temperatureTolerance) ?? 2;

            card.classList.remove("is-match", "is-near", "is-review", "is-pending");

            if (
                soilPh === null ||
                temperature === null ||
                phMin === null ||
                phMax === null ||
                temperatureMin === null ||
                temperatureMax === null
            ) {
                card.classList.add("is-pending");

                if (status) {
                    status.textContent = "Awaiting readings";
                }

                if (note) {
                    note.textContent = "Waiting for live pH and temperature data.";
                }

                return;
            }

            const phMatches = soilPh >= phMin && soilPh <= phMax;
            const temperatureMatches = temperature >= temperatureMin && temperature <= temperatureMax;
            const phIsNear = soilPh >= phMin - phTolerance && soilPh <= phMax + phTolerance;
            const temperatureIsNear = temperature >= temperatureMin - temperatureTolerance
                && temperature <= temperatureMax + temperatureTolerance;

            if (phMatches && temperatureMatches) {
                card.classList.add("is-match");

                if (status) {
                    status.textContent = "Potential match";
                }

                if (note) {
                    note.textContent = "pH and temperature match. Full field validation is still required.";
                }
            } else if (phIsNear && temperatureIsNear) {
                card.classList.add("is-near");

                if (status) {
                    status.textContent = "Near threshold";
                }

                if (note) {
                    note.textContent = "A direct condition is near its range. Review before planting.";
                }
            } else {
                card.classList.add("is-review");

                if (status) {
                    status.textContent = "Needs adjustment";
                }

                if (note) {
                    note.textContent = "Current pH or temperature is outside the configured range.";
                }
            }
        });
    };

    const setSensorState = (name, label, tone = "good") => {
        document.querySelectorAll(`[data-sensor-state="${name}"]`).forEach((element) => {
            element.textContent = label;
            element.classList.toggle("good", tone === "good");
            element.classList.toggle("warn", tone === "warn");
            element.classList.toggle("bad", tone === "bad");
        });
    };

    const updateSensorStates = (reading) => {
        const temperature = numberValue(reading.temperature);
        const moisture = numberValue(reading.soil_moisture);
        const ph = numberValue(reading.soil_ph);
        const ec = numberValue(reading.soil_ec);
        const nitrogen = numberValue(reading.nitrogen);
        const phosphorus = numberValue(reading.phosphorus);
        const potassium = numberValue(reading.potassium);

        setSensorState("temperature", temperature !== null && temperature >= 22 && temperature <= 38 ? "Normal" : "Check", temperature !== null && temperature >= 22 && temperature <= 38 ? "good" : "warn");
        setSensorState("moisture", moisture !== null ? "Validation Required" : "No Reading", "warn");
        setSensorState("ph", ph > 0 && ph >= 5.5 && ph <= 7 ? "Suitable" : "Check pH", ph > 0 && ph >= 5.5 && ph <= 7 ? "good" : "warn");
        setSensorState("ec", ec !== null && ec > 0 ? "Range Needed" : "No Reading", "warn");
        setSensorState("nitrogen", nitrogen !== null ? "Validation Required" : "No Reading", "warn");
        setSensorState("phosphorus", phosphorus !== null ? "Validation Required" : "No Reading", "warn");
        setSensorState("potassium", potassium !== null ? "Validation Required" : "No Reading", "warn");
    };

    const showEmptyState = () => {
        setText('[data-reading="soil_moisture"]', "--%");
        setText('[data-reading="soil_ph"]', "--");
        setText('[data-reading="soil_ec"]', "--");
        setText('[data-reading="npk"]', "-- / -- / --");
        setText('[data-reading="nitrogen"]', "--");
        setText('[data-reading="phosphorus"]', "--");
        setText('[data-reading="potassium"]', "--");
        setText('[data-reading="temperature"]', "-- C");

        setText('[data-reading-status="soil_moisture"]', "Awaiting sensor reading");
        setText('[data-reading-status="soil_ph"]', "pH sensor not connected");
        setText('[data-reading-status="soil_ec"]', "Electrical conductivity");
        setText('[data-reading-status="npk"]', "Awaiting NPK sensor reading");
        setText('[data-reading-status="temperature"]', "Field climate reading");

        setText("[data-recommendation-score]", "--");
        setText("[data-recommendation-title]", "No crop selected yet");
        setText("[data-activity-title]", "Sensor readings pending");
        setText("[data-activity-detail]", "Waiting for device input");
        setSystemStatus("offline", "Sensor Offline");

        setBar("moisture", 0);
        setBar("nutrients", 0);
        setBar("climate", 0);
        setBar("overall", 0);
        updateCropCards();

        ["temperature", "moisture", "ph", "ec", "nitrogen", "phosphorus", "potassium"].forEach((name) => {
            setSensorState(name, "No Reading", "warn");
        });
    };

    const updateDashboard = (reading) => {
        if (!reading) {
            showEmptyState();
            return;
        }

        const nitrogen = numberValue(reading.nitrogen);
        const phosphorus = numberValue(reading.phosphorus);
        const potassium = numberValue(reading.potassium);
        const soilPh = numberValue(reading.ph);
        const soilEc = numberValue(reading.ec);
        const npk = `${nutrientText(nitrogen)} / ${nutrientText(phosphorus)} / ${nutrientText(potassium)}`;
        const updatedAt = relativeTime(reading.age_seconds);
        const connectionStatus = reading.connection_status || "Offline";
        const isLive = reading.is_live === true || reading.is_live === "1" || connectionStatus === "Live" || connectionStatus === "Online";
        const isStale = connectionStatus === "Stale";
        const freshnessLabel = isLive ? `Updated ${updatedAt}` : `${isStale ? "Stale reading" : "Last reading"} ${updatedAt}`;

        setText('[data-reading="soil_moisture"]', percentText(reading.moisture));
        setText('[data-reading="soil_ph"]', soilPh === null ? "--" : fixed(soilPh, 1));
        setText('[data-reading="soil_ec"]', soilEc === null ? "--" : fixed(soilEc, 0));
        setText('[data-reading="npk"]', npk);
        setText('[data-reading="nitrogen"]', nutrientText(nitrogen));
        setText('[data-reading="phosphorus"]', nutrientText(phosphorus));
        setText('[data-reading="potassium"]', nutrientText(potassium));
        setText('[data-reading="temperature"]', celsiusText(reading.temperature));

        setText('[data-reading-status="soil_moisture"]', freshnessLabel);
        setText('[data-reading-status="soil_ph"]', soilPh === null ? "No pH reading" : freshnessLabel);
        setText('[data-reading-status="soil_ec"]', soilEc === null ? "No EC reading" : `uS/cm ${freshnessLabel.toLowerCase()}`);
        setText('[data-reading-status="npk"]', `N, P, K mg/kg ${isLive ? "updated" : "last updated"} ${updatedAt}`);
        setText('[data-reading-status="temperature"]', freshnessLabel);
        updateSensorStates({
            ...reading,
            soil_moisture: reading.moisture,
            soil_ph: reading.ph,
            soil_ec: reading.ec
        });

        const moistureValue = numberValue(reading.moisture);
        const moistureScore = moistureValue === null ? 0 : Math.round(clamp(moistureValue, 0, 100));
        const climateScore = Math.round((
            rangeScore(reading.temperature, 22, 34) +
            rangeScore(reading.temperature, 22, 34)
        ) / 2);
        const nutrientValues = [nitrogen, phosphorus, potassium].filter((value) => value !== null);
        const nutrientScore = nutrientValues.length === 3
            ? Math.round(clamp((nitrogen + phosphorus + potassium) / 3, 0, 100))
            : 0;
        const nutrientLabel = nutrientValues.length === 3
            ? `${nutrientText(nitrogen)}/${nutrientText(phosphorus)}/${nutrientText(potassium)}`
            : "--";
        const overallScore = Math.round((moistureScore + climateScore + nutrientScore) / 3);

        setBar("moisture", moistureScore, moistureValue === null ? "--" : `${fixed(moistureValue, 0)}%`);
        setBar("nutrients", nutrientScore, nutrientLabel);
        setBar("climate", climateScore);
        setBar("overall", overallScore);
        updateCropCards(reading);

        setText("[data-recommendation-score]", `${overallScore}%`);
        setText("[data-recommendation-title]", isLive
            ? "Pending full validation"
            : "Waiting for live sensor update");
        setText("[data-activity-title]", isLive ? "Live sensor reading received" : (isStale ? "Stale sensor reading" : "Sensor offline"));
        setText("[data-activity-detail]", `${reading.device_name || "CropSense device"} ${isLive ? "updated" : "last updated"} ${updatedAt}`);
        setSystemStatus(isLive ? "live" : (isStale ? "stale" : "offline"), isLive ? "Sensor Live" : (isStale ? "Sensor Stale" : "Sensor Offline"));
    };

    const loadLatestReading = async () => {
        try {
            const response = await fetch(apiUrl, { cache: "no-store" });

            if (!response.ok) {
                throw new Error("Reading request failed");
            }

            const payload = await response.json();

            if (!payload.ok) {
                throw new Error(payload.error || "Invalid sensor response");
            }

            updateDashboard(payload.data || null);
        } catch (error) {
            setText("[data-activity-title]", "Unable to refresh readings");
            setText("[data-activity-detail]", "Check the local server and database connection");
            setSystemStatus("offline", "Sensor Status Unavailable");
        }
    };

    setText("[data-activity-title]", "Loading latest sensor reading");
    setText("[data-activity-detail]", "Connecting to the CropSense sensor API");
    loadLatestReading();
    window.setInterval(loadLatestReading, pollMs);
    updateDashboardClock();
    window.setInterval(updateDashboardClock, 1000);
})();
