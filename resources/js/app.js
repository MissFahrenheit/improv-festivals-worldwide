import "./bootstrap.js";

// Function to handle image loading errors
function handleImageErrors() {
    document.querySelectorAll(".festival-image").forEach((img) => {
        img.onerror = function () {
            img.remove();
        };
    });
}

// Function to handle tab clicks
function setupTabs() {
    const tabs = document.querySelectorAll(".tab");

    tabs.forEach((clickedTab) => {
        clickedTab.addEventListener("click", () => {
            const clickedContinent = clickedTab.dataset.continent;

            // Hide all content sections and remove all active classes
            tabs.forEach((tab) => {
                tab.classList.remove("active");

                const continent = tab.dataset.continent;
                const contentDiv = document.querySelector(
                    `[data-content-continent="${continent}"]`,
                );
                if (contentDiv) {
                    contentDiv.style.display = "none";
                }
            });

            // Show the clicked tab's content
            const selectedContentDiv = document.querySelector(
                `[data-content-continent="${clickedContinent}"]`,
            );
            if (selectedContentDiv) {
                selectedContentDiv.style.display = "block";
                clickedTab.classList.add("active");
            }
        });
    });
}

// Initialize tabs and load default content on page load
document.addEventListener("DOMContentLoaded", () => {
    setupTabs(); // Set up click handlers for tabs
    handleImageErrors();
});
