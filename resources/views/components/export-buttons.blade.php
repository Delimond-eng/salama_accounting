{{-- Requiert exportBase + queryParams() ou buildExportParams() sur l'instance Vue --}}
<div class="dropdown" v-if="exportBase">
    <a href="javascript:void(0);" class="btn btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
        <i class="ti ti-package-export me-1"></i>Exporter
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item" :href="exportUrl('pdf')" target="_blank">
                <i class="ti ti-file-type-pdf me-2 text-danger"></i>PDF
            </a>
        </li>
        <li>
            <a class="dropdown-item" :href="exportUrl('excel')" target="_blank">
                <i class="ti ti-file-type-xls me-2 text-success"></i>Excel
            </a>
        </li>
        <li>
            <a class="dropdown-item" :href="exportUrl('csv')" target="_blank">
                <i class="ti ti-file-spreadsheet me-2 text-info"></i>CSV
            </a>
        </li>
    </ul>
</div>
