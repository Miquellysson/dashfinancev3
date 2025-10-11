document.addEventListener('DOMContentLoaded', () => {
  const filtersForm = document.querySelector('#templateFilters');
  if (filtersForm) {
    filtersForm.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', () => filtersForm.submit());
    });
  }

  const projectNameInput = document.querySelector('#projectNameInput');
  const projectSlugInput = document.querySelector('#projectSlugInput');
  if (projectNameInput && projectSlugInput) {
    let slugTouched = false;

    projectSlugInput.addEventListener('input', () => {
      slugTouched = projectSlugInput.value.trim() !== '';
    });

    projectSlugInput.addEventListener('focus', () => {
      slugTouched = projectSlugInput.value.trim() !== '';
    });

    const updateSlug = () => {
      if (slugTouched) {
        return;
      }
      projectSlugInput.value = slugify(projectNameInput.value);
    };

    projectNameInput.addEventListener('input', updateSlug);
    updateSlug();
  }
});

function slugify(text) {
  return text
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-');
}
