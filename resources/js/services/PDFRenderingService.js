import * as pdfjsLib from 'pdfjs-dist';
import pdfWorkerSrc from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerSrc;

export async function loadPDF(url) {
    return pdfjsLib.getDocument({
        url,
        withCredentials: false,
    }).promise;
}

/**
 * Render a PDF page into a canvas, fitting it within the given pixel bounds.
 * The canvas is sized to exactly fill availableWidth × availableHeight while
 * preserving the page's aspect ratio (letterboxed if necessary).
 *
 * @param {PDFDocumentProxy} pdfDoc
 * @param {number}           pageNum
 * @param {HTMLCanvasElement} canvas
 * @param {number}           availableWidth   - container width  in CSS px
 * @param {number}           availableHeight  - container height in CSS px
 */
export async function renderPDFPage(pdfDoc, pageNum, canvas, availableWidth, availableHeight) {
    if (!pdfDoc || !canvas) return null;

    const page = await pdfDoc.getPage(pageNum);
    const baseViewport = page.getViewport({ scale: 1 });

    const fitScale = Math.min(
        availableWidth  / baseViewport.width,
        availableHeight / baseViewport.height,
    );

    const viewport = page.getViewport({ scale: fitScale });
    const ratio    = window.devicePixelRatio || 1;

    // Physical pixel size (sharp on HiDPI)
    canvas.width  = Math.floor(viewport.width  * ratio);
    canvas.height = Math.floor(viewport.height * ratio);

    // CSS size — never larger than the container
    canvas.style.width  = `${Math.floor(viewport.width)}px`;
    canvas.style.height = `${Math.floor(viewport.height)}px`;
    canvas.style.maxWidth  = '100%';
    canvas.style.maxHeight = '100%';

    const context = canvas.getContext('2d');
    if (!context) throw new Error('Unable to obtain canvas context.');

    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    context.clearRect(0, 0, canvas.width, canvas.height);

    await page.render({ canvasContext: context, viewport }).promise;

    return canvas;
}

/**
 * Render a PDF page thumbnail that fills its container exactly.
 * The canvas is sized to 100 % of the container's CSS dimensions so that
 * Tailwind's `h-full w-full` on the canvas element works correctly.
 *
 * @param {PDFDocumentProxy} pdfDoc
 * @param {number}           pageNum
 * @param {HTMLCanvasElement} canvas
 * @param {number}           targetWidth  - desired render width in CSS px
 */
export async function renderPDFThumbnail(pdfDoc, pageNum, canvas, targetWidth = 200) {
    if (!pdfDoc || !canvas) return;

    const page = await pdfDoc.getPage(pageNum);
    const baseViewport = page.getViewport({ scale: 1 });
    const scale    = targetWidth / baseViewport.width;
    const viewport = page.getViewport({ scale });
    const ratio    = window.devicePixelRatio || 1;

    // Physical pixel buffer
    canvas.width  = Math.floor(viewport.width  * ratio);
    canvas.height = Math.floor(viewport.height * ratio);

    // Let CSS control the display size — the parent div is aspect-video
    // and the canvas has h-full w-full, so remove any inline dimension
    // that would fight the layout.
    canvas.style.width  = '100%';
    canvas.style.height = '100%';

    const context = canvas.getContext('2d');
    if (!context) return;

    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    context.clearRect(0, 0, canvas.width, canvas.height);

    await page.render({ canvasContext: context, viewport }).promise;
}
