<style>
    .pos-receipt {
        width: 70mm;
        max-width: 100%;
        margin: 0 auto;
        color: #000;
        background: #fff;
    }
    .pos-receipt *, .pos-receipt *::before, .pos-receipt *::after {
        box-sizing: border-box;
    }
    .pos-receipt .whitespace-nowrap {
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .pos-receipt table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 10px;
        line-height: 1.3;
    }
    .pos-receipt th.w-20 { width: 23%; }
    .pos-receipt td, .pos-receipt th {
        padding: 1px 2px;
        overflow-wrap: anywhere;
    }
    .pos-receipt .text-xs\/4 { font-size: 11px; line-height: 1.35; }
    .pos-receipt h5 { font-size: 18px; line-height: 1.3; }
    .pos-receipt .mt-10 { margin-top: 5mm; }
    .pos-receipt .uddds-slip { padding: 2mm 0; }
    .pos-receipt .uddds-slip + .uddds-slip {
        border-top: 1px dashed #000;
        margin-top: 3mm;
    }
    @media print {
        @page { size: auto; margin: 2mm; }
        html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
        body { transform: none !important; }
        .min-h-screen { min-height: 0 !important; }
        .pos-print-page { width: 100%; max-width: none; margin: 0; padding: 0; }
        .pos-print-page .no-print { display: none !important; }
        .pos-receipt { margin: 0 auto; }
        .pos-receipt .uddds-slip {
            page-break-before: auto;
            page-break-after: auto;
            break-before: auto;
            break-after: auto;
            break-inside: auto;
        }
        .pos-receipt tr { break-inside: avoid; }
    }
</style>
