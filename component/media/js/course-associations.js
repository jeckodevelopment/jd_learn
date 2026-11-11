const filterAssociationOptions = (input) => {
  const wrapper = input.closest('.jlms-association-picker');
  const select = wrapper?.querySelector('select');
  if (!select) return;

  const term = input.value.trim().toLocaleLowerCase();
  for (const option of select.options) {
    // Keep the empty choice available regardless of the search text.
    option.hidden = option.value !== '0' && !option.selected && !option.text.toLocaleLowerCase().includes(term);
  }
};

document.addEventListener('input', (event) => {
  if (event.target.matches('[data-jlms-association-search]')) {
    filterAssociationOptions(event.target);
  }
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-jlms-association-search]').forEach(filterAssociationOptions);
});
