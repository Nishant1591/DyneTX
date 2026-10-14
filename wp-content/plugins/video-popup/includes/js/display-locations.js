document.addEventListener('DOMContentLoaded', function() {

    function videoPopupDisplayLocations() {
        // Get all the location checkboxes

        const locationGroup = document.querySelector('.vp-locations-group');
        if ( !locationGroup ) {
            return;
        }

        const entireSiteCheckbox = locationGroup.querySelector('input[value="entire"]');
        const entireSiteExWooCheckbox = locationGroup.querySelector('input[value="entire_ex_woo"]');
        const locationCheckboxes = document.querySelectorAll('.vp-locations-group input[type="checkbox"]');
        
        // Function to disable/enable other checkboxes
        function toggleOtherCheckboxes(shouldDisable, selectedCheckbox) {
            locationCheckboxes.forEach(function(checkbox) {
                // Skip the currently selected checkbox
                if (checkbox === selectedCheckbox) {
                    return;
                }
                
                // Disable/enable the checkbox
                checkbox.disabled = shouldDisable;
                
                // Add visual indication of disabled state
                if (shouldDisable) {
                    checkbox.checked = false; // Uncheck the checkbox if we're disabling it
                    checkbox.parentNode.classList.add('vp-checkbox-disabled');
                } else {
                    checkbox.parentNode.classList.remove('vp-checkbox-disabled');
                }
            });
        }
        
        // Function to handle "Entire Site" checkbox change
        function handleEntireSiteChange(event) {
            const clickedCheckbox = event.target;
            const isChecked = clickedCheckbox.checked;
            
            // If checkbox is checked, disable all other options
            if (isChecked) {
                toggleOtherCheckboxes(true, clickedCheckbox);
            } else {
                // If checkbox is unchecked, enable all options
                toggleOtherCheckboxes(false, clickedCheckbox);
            }
        }
        
        // Initial check for pre-selected values
        function checkInitialState() {
            if (entireSiteCheckbox && entireSiteCheckbox.checked) {
                toggleOtherCheckboxes(true, entireSiteCheckbox);
            } else if (entireSiteExWooCheckbox && entireSiteExWooCheckbox.checked) {
                toggleOtherCheckboxes(true, entireSiteExWooCheckbox);
            }
        }
        
        // Add event listeners to entire site checkboxes
        if (entireSiteCheckbox) {
            entireSiteCheckbox.addEventListener('change', handleEntireSiteChange);
        }
        
        if (entireSiteExWooCheckbox) {
            entireSiteExWooCheckbox.addEventListener('change', handleEntireSiteChange);
        }
        
        // Check initial state
        checkInitialState();
    }

    videoPopupDisplayLocations();

});