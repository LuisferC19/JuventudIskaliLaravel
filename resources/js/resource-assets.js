const images = import.meta.glob('../img/**/*', {
    eager: true,
    query: '?url',
    import: 'default',
});

const icons = import.meta.glob('../iconos/**/*', {
    eager: true,
    query: '?url',
    import: 'default',
});

export function applyResourceAssets(root = document) {
    root.querySelectorAll('img[src*="./public/img/"], img[src*="./public/iconos/"]').forEach((image) => {
        const source = image.getAttribute('src');
        const directory = source.includes('/iconos/') ? 'iconos' : 'img';
        const filename = source.split('/').pop();
        const asset = (directory === 'iconos' ? icons : images)[`../${directory}/${filename}`];

        if (asset) {
            image.src = asset;
        }
    });
}

applyResourceAssets();
