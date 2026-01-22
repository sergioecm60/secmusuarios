function adjustFormFieldsForSystem(sistema) {
    const nombreContainer = document.getElementById('nombreContainer');
    const apellidoContainer = document.getElementById('apellidoContainer');
    const nombreCompletoContainer = document.getElementById('nombreCompletoContainer');
    const fullNameContainer = document.getElementById('fullNameContainer');

    // Reset all to hidden
    if(nombreContainer) nombreContainer.style.display = 'none';
    if(apellidoContainer) apellidoContainer.style.display = 'none';
    if(nombreCompletoContainer) nombreCompletoContainer.style.display = 'none';
    if(fullNameContainer) fullNameContainer.style.display = 'none';

    switch (sistema) {
        case 'secmalquileres':
        case 'secmrrhh':
            if(nombreCompletoContainer) nombreCompletoContainer.style.display = 'block';
            break;
        case 'secmti':
            if(fullNameContainer) fullNameContainer.style.display = 'block';
            break;
        case 'secmautos':
        case 'secmagencias':
            if(nombreContainer) nombreContainer.style.display = 'block';
            if(apellidoContainer) apellidoContainer.style.display = 'block';
            break;
        case 'Psitios':
            // No name fields needed
            break;
        case 'secmusuarios':
        default:
            // For the master system, let's show all for maximum flexibility
            if(nombreContainer) nombreContainer.style.display = 'block';
            if(apellidoContainer) apellidoContainer.style.display = 'block';
            if(nombreCompletoContainer) nombreCompletoContainer.style.display = 'block';
            if(fullNameContainer) fullNameContainer.style.display = 'block';
            break;
    }
}
