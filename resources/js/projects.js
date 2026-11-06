import { setPreview, setupCrud } from './crud';

const TEXT_FIELDS = [
    'name_en', 'name_bn', 'slug', 'tagline_en', 'tagline_bn', 'handover', 'area_name_en', 'area_name_bn', 'area_slug',
    'address_en', 'address_bn', 'cover_alt_en', 'cover_alt_bn', 'seo_title_en', 'seo_title_bn', 'seo_description_en', 'seo_description_bn',
];

setupCrud({
    noun: 'project',
    where: 'website',
    files: ['cover_file'],
    checkboxes: ['is_featured'],
    fill(form, project) {
        for (const name of TEXT_FIELDS) {
            form.elements[name].value = project?.[name] ?? '';
        }
        form.elements.status.value = project?.status ?? 'ongoing';
        form.elements.type.value = project?.type ?? 'residential';
        form.elements.is_featured.checked = !!project?.is_featured;

        [0, 1, 2].forEach((row) => {
            const stat = project?.card_stats?.[row];
            form.elements[`stats[${row}][value_en]`].value = stat?.value?.en ?? '';
            form.elements[`stats[${row}][value_bn]`].value = stat?.value?.bn ?? '';
            form.elements[`stats[${row}][label_en]`].value = stat?.label?.en ?? '';
            form.elements[`stats[${row}][label_bn]`].value = stat?.label?.bn ?? '';
        });

        setPreview('cover_file', project?.cover);
    },
});
