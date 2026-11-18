export function isNumericTabCode(code) {
    return /^\d+$/.test(String(code ?? ''));
}

export function prefixFromCode(code) {
    const value = String(code ?? '').toUpperCase();
    const hyphenated = value.match(/^([A-Z0-9]+)-(\d+)$/);

    if (hyphenated) {
        return hyphenated[1];
    }

    const match = value.match(/^([A-Z]*)(\d+)$/);

    if (match) {
        return match[1];
    }

    if (/^[A-Z]+$/.test(value)) {
        return value;
    }

    return '';
}

export function prefixEndsWithDigit(prefix) {
    return /[0-9]$/.test(String(prefix ?? ''));
}

export function formatPrefixLabel(prefix) {
    const value = String(prefix ?? '').toUpperCase();

    if (!value) {
        return '';
    }

    return prefixEndsWithDigit(value) ? `${value}-` : value;
}

export function tabSeriesDomId(prefix) {
    const value = String(prefix ?? '').toUpperCase();

    return `tab-series-${value || 'numeric'}`;
}

export function rowsOfTen(tabs) {
    const rows = [];

    for (let index = 0; index < tabs.length; index += 10) {
        rows.push(tabs.slice(index, index + 10));
    }

    return rows;
}

function numericSuffix(code) {
    const match = String(code ?? '').match(/(\d+)$/);

    return match ? Number(match[1]) : 0;
}

export function sortTabsInSeries(tabs) {
    return [...tabs].sort((a, b) => {
        const byNumber = numericSuffix(a.code) - numericSuffix(b.code);

        if (byNumber !== 0) {
            return byNumber;
        }

        return String(a.code).localeCompare(String(b.code), undefined, { numeric: true });
    });
}

function sortedPrefixes(prefixes) {
    return [...prefixes].sort((left, right) => {
        if (left === '' && right !== '') {
            return -1;
        }

        if (right === '' && left !== '') {
            return 1;
        }

        return left.localeCompare(right);
    });
}

export function groupTabsByPrefix(tabs) {
    const groups = new Map();

    for (const tab of tabs ?? []) {
        const prefix = prefixFromCode(tab.code);

        if (!groups.has(prefix)) {
            groups.set(prefix, []);
        }

        groups.get(prefix).push(tab);
    }

    return sortedPrefixes(groups.keys()).map((prefix) => {
        const sorted = sortTabsInSeries(groups.get(prefix));

        return {
            prefix,
            tabs: sorted,
            rows: rowsOfTen(sorted),
        };
    });
}

export function groupTabsForDisplay(tabs) {
    return groupTabsByPrefix(tabs).flatMap((group) => group.rows);
}
