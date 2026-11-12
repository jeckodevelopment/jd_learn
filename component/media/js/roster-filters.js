for (const search of document.querySelectorAll('[data-roster-option-search]')) {
    const select = document.getElementById(search.dataset.rosterOptionSearch);

    if (!(select instanceof HTMLSelectElement)) {
        continue;
    }

    const filterOptions = () => {
        const term = search.value.trim().toLocaleLowerCase();

        for (const option of select.options) {
            if (option.value === '0' || option.selected) {
                option.hidden = false;
                continue;
            }

            option.hidden = term !== '' && !option.textContent.toLocaleLowerCase().includes(term);
        }
    };

    search.addEventListener('input', filterOptions);
    filterOptions();
}
