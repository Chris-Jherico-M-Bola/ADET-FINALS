import * as pdfjsLib from 'pdfjs-dist';
import pdfWorkerSrc from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerSrc;

export async function loadPDF(url) {
    return pdfjsLib.getDocument({
        url,
        withCredentials: false,
    }).promise;
}

export async function renderPDFPage(pdfDoc, pageNum, canvas, scale = 1.3) {
    if (!pdfDoc || !canvas) {
        return null;
    }

    const page = await pdfDoc.getPage(pageNum);
    const viewport = page.getViewport({ scale });
    const context = canvas.getContext('2d');

    if (!context) {
        throw new Error('Unable to obtain canvas context.');
    }

    const ratio = window.devicePixelRatio || 1;
    canvas.width = Math.floor(viewport.width * ratio);
    canvas.height = Math.floor(viewport.height * ratio);
    canvas.style.width = `${viewport.width}px`;
    canvas.style.height = `${viewport.height}px`;

    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    context.clearRect(0, 0, canvas.width, canvas.height);

    await page.render({
        canvasContext: context,
        viewport,
    }).promise;
}

export async function renderPDFThumbnail(pdfDoc, pageNum, canvas, targetWidth = 200) {
    if (!pdfDoc || !canvas) {
        return;
    }

    const page = await pdfDoc.getPage(pageNum);
    const baseViewport = page.getViewport({ scale: 1 });
    const scale = targetWidth / baseViewport.width;
    const viewport = page.getViewport({ scale });
    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    const ratio = window.devicePixelRatio || 1;
    canvas.width = Math.floor(viewport.width * ratio);
    canvas.height = Math.floor(viewport.height * ratio);
    canvas.style.width = `${viewport.width}px`;
    canvas.style.height = `${viewport.height}px`;

    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    context.clearRect(0, 0, canvas.width, canvas.height);

    const renderTask = page.render({
        canvasContext: context,
        viewport,
    });

    await renderTask.promise;
}
