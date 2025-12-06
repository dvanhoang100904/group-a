// Sanitize methods để tránh XSS
export const sanitizeOutput = (value) => {
    if (value === null || value === undefined) return '';
    const div = document.createElement('div');
    div.textContent = value.toString();
    return div.innerHTML;
};

export const sanitizeInput = (value) => {
    if (value === null || value === undefined) return '';
    const div = document.createElement('div');
    div.textContent = value.toString();
    return div.innerHTML.replace(/[^\w\s\-_.]/gi, '').substring(0, 255);
};

export const sanitizeUrl = (url) => {
    if (!url) return '';
    try {
        const parsed = new URL(url, window.location.origin);
        if (parsed.origin === window.location.origin || parsed.protocol === 'about:') {
            return url;
        }
        return '';
    } catch {
        return '';
    }
};

export const validateFolderId = (folderId) => {
    if (!folderId || !Number.isInteger(Number(folderId)) || folderId <= 0) {
        throw new Error('ID thư mục không hợp lệ');
    }
    return Number(folderId);
};

export const validateDocumentId = (documentId) => {
    if (!documentId || !Number.isInteger(Number(documentId)) || documentId <= 0) {
        throw new Error('ID tài liệu không hợp lệ');
    }
    return Number(documentId);
};

export const sanitizeUrlParam = (param) => {
    if (!param) return '';
    return encodeURIComponent(param.toString());
};