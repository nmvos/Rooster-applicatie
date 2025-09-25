document.addEventListener("DOMContentLoaded", () => {
    // Select all elements representing medewerkers
    const medewerkers = document.querySelectorAll(".medewerker");

    medewerkers.forEach((user) => {
        // Check if the medewerker is unregistered (e.g., has a specific class or data attribute)
        if (user.classList.contains("unregistered")) {
            // Highlight the unregistered medewerker
            user.style.backgroundColor = "#ffcccc"; // Light red background
            user.style.border = "2px solid #ff0000"; // Red border
        }
    });
});