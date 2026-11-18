const ABBREVIATIONS = {
    jd: 'jardim',
    vl: 'vila',
    pq: 'parque',
    res: 'residencial',
    dist: 'distrito',
    chac: 'chacara',
};

const ACCENTS = {
    á: 'a', à: 'a', ã: 'a', â: 'a', ä: 'a',
    é: 'e', è: 'e', ê: 'e', ë: 'e',
    í: 'i', ì: 'i', î: 'i', ï: 'i',
    ó: 'o', ò: 'o', õ: 'o', ô: 'o', ö: 'o',
    ú: 'u', ù: 'u', û: 'u', ü: 'u',
    ç: 'c', ñ: 'n',
};

export const emptyDeliveryAddress = () => ({
    zip_code: '',
    street: '',
    number: '',
    complement: '',
    delivery_fee_zone_id: '',
    neighborhood: '',
    city: '',
    state: '',
    reference_point: '',
    delivery_phone: '',
    delivery_fee: null,
});

export const zipDigits = (value) => String(value ?? '').replace(/\D/g, '').slice(0, 8);

export const formatZip = (value) => {
    const digits = zipDigits(value);

    if (digits.length <= 5) {
        return digits;
    }

    return `${digits.slice(0, 5)}-${digits.slice(5)}`;
};

export const normalizeNeighborhood = (name) => {
    let value = String(name ?? '').trim().toLowerCase();

    if (!value) {
        return '';
    }

    value = value.replace(/[áàãâäéèêëíìîïóòõôöúùûüçñ]/g, (char) => ACCENTS[char] ?? char);
    value = value.replace(/\./g, ' ').replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();

    return value
        .split(' ')
        .map((part) => ABBREVIATIONS[part] ?? part)
        .filter(Boolean)
        .join(' ');
};

export const matchNeighborhood = (needle, neighborhoods) => {
    const normalizedNeedle = normalizeNeighborhood(needle);

    if (!normalizedNeedle) {
        return null;
    }

    return (neighborhoods ?? []).find((neighborhood) => (
        neighborhood?.has_label !== false
        && normalizeNeighborhood(neighborhood?.name) === normalizedNeedle
    )) ?? null;
};

export const formatDeliveryAddressLine = (address) => {
    const street = String(address?.street ?? '').trim();
    const number = String(address?.number ?? '').trim();
    const complement = String(address?.complement ?? '').trim();
    const neighborhood = String(address?.neighborhood ?? '').trim();
    const reference = String(address?.reference_point ?? '').trim();
    const phone = String(address?.delivery_phone ?? '').trim();
    const head = [street, number, complement].filter(Boolean).join(', ');

    return [head, neighborhood, reference ? `(ref: ${reference})` : '', phone ? `Tel: ${phone}` : '']
        .filter(Boolean)
        .join(' – ');
};

export const deliveryMapsUrl = (address) => {
    const query = [
        address?.street,
        address?.number,
        address?.neighborhood,
        address?.city,
        address?.state,
    ].map((part) => String(part ?? '').trim()).filter(Boolean).join(', ');

    if (!query) {
        return null;
    }

    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
};
