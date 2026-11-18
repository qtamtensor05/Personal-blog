/** Make a temporary AI-only copy. The media upload always keeps the original file. */
export async function prepareAnalysisImage(file) {
    if (file.size <= 256 * 1024 || typeof createImageBitmap !== 'function') return null;
    let bitmap;
    try {
        bitmap = await createImageBitmap(file);
        for (const maxSide of [1024, 768]) {
            const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(bitmap.width * scale));
            canvas.height = Math.max(1, Math.round(bitmap.height * scale));
            const context = canvas.getContext('2d');
            if (!context) return null;
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.8));
            if (blob && blob.size <= 1024 * 1024 && blob.size < file.size) return blob;
        }
    } catch {
        // Unsupported decoding/encoding must not prevent the original upload or AI request.
    } finally {
        bitmap?.close();
    }
    return null;
}
