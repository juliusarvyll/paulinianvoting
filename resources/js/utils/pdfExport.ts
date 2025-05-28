import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

export const exportToPDF = async (elementId: string, filename: string) => {
    try {
        const element = document.getElementById(elementId);
        if (!element) return;

        // Create a clone of the element to modify for PDF
        const clone = element.cloneNode(true) as HTMLElement;
        clone.style.backgroundColor = 'white';
        clone.style.padding = '20px';
        document.body.appendChild(clone);

        // Force all progress bars to be visible
        const progressBars = clone.querySelectorAll('[style*="width"]');
        progressBars.forEach(bar => {
            const width = (bar as HTMLElement).style.width;
            (bar as HTMLElement).style.minWidth = width;
        });

        // Adjust scrollable areas
        const scrollAreas = clone.querySelectorAll('.max-h-48');
        scrollAreas.forEach(area => {
            area.classList.remove('max-h-48');
            area.classList.remove('overflow-y-auto');
        });

        const canvas = await html2canvas(clone, {
            scale: 2,
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff'
        });

        document.body.removeChild(clone);

        const imgWidth = 210; // A4 width in mm
        const pageHeight = 297; // A4 height in mm
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        const pdf = new jsPDF('p', 'mm', 'a4');

        let heightLeft = imgHeight;
        let position = 0;
        let pageNumber = 1;

        // Add first page
        pdf.addImage(
            canvas.toDataURL('image/jpeg', 0.8),
            'JPEG',
            0,
            position,
            imgWidth,
            imgHeight
        );
        heightLeft -= pageHeight;

        // Add subsequent pages if needed
        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(
                canvas.toDataURL('image/jpeg', 0.8),
                'JPEG',
                0,
                position,
                imgWidth,
                imgHeight
            );
            heightLeft -= pageHeight;
            pageNumber++;
        }

        // Add page numbers
        for (let i = 1; i <= pageNumber; i++) {
            pdf.setPage(i);
            pdf.setFontSize(10);
            pdf.text(`Page ${i} of ${pageNumber}`, pdf.internal.pageSize.getWidth() - 30, pdf.internal.pageSize.getHeight() - 10);
        }

        pdf.save(filename);
    } catch (error) {
        console.error('Error generating PDF:', error);
    }
};
