window.MPC_SCRIPT_LOADED = true;
const ASSET = (name) => {
  const base = (window.MPC_ASSET_BASE || 'assets').replace(/\/+$/, '');
  return `${base}/${name}`;
};

function i18n(key, fallback) {
  if (window.CRM_I18N && typeof window.CRM_I18N[key] !== 'undefined') {
    return window.CRM_I18N[key];
  }
  return fallback;
}
const TYPE_DEFAULTS = {
  other: {
    title: 'Financial Offer for……………Service',
    intro: 'We are pleased to present our pricing structure for the requested service, tailored to meet your specific requirements and ensure the best value for your investment.',
    lead: 'PLS , find the next financial offer',
    bg: ASSET('mpc-other-bg.png'),
    paymentDue: ['Upon requesting the service', 'Upon receiving the service'],
    rows: () => Array.from({ length: 3 }, () => ({ item: '', values: ['', '', ''], remarks: '' }))
  },
  iso: {
    title: 'Financial Offer  for ISO Certificates',
    intro: 'Thank you for the opportunity to submit this proposal. We are pleased to present our pricing structure tailored to meet your specific requirements and ensure the highest value for your investment.',
    lead: 'PLS , find the next financial offer',
    bg: ASSET('mpc-iso-bg.png'),
    paymentDue: ['Contract Signing', 'Certificate Issuance'],
    rows: () => ['ISO …………', 'ISO …………', 'ISO …………'].map(item => ({ item, values: ['', '', '', ''], remarks: '' }))
  },
  inspection: {
    title: 'Financial Offer  for Inspection Services & HACCP Certificate',
    intro: 'We are pleased to present our pricing structure for the requested service, tailored to meet your specific requirements and ensure the best value for your investment.',
    lead: 'PLS , find the next financial offer',
    bg: ASSET('mpc-inspection-bg.png'),
    paymentDue: ['Contract Signing', 'Certificate Issuance'],
    rows: () => ['FS Inspection', 'Labeling', 'COC'].map(item => ({ item, values: ['', ''], remarks: '' }))
  }
};

const els = {
  form: document.getElementById('quoteForm'),
  offerType: document.getElementById('offerType'),
  clientName: document.getElementById('clientName'),
  quotationNo: document.getElementById('quotationNo'),
  quoteDate: document.getElementById('quoteDate'),
  vatRate: document.getElementById('vatRate'),
  offerTitle: document.getElementById('offerTitle'),
  introText: document.getElementById('introText'),
  leadText: document.getElementById('leadText'),
  tableEditor: document.getElementById('tableEditor'),
  haccpPanel: document.getElementById('haccpPanel'),
  haccpEditor: document.getElementById('haccpEditor'),
  showVatRow: document.getElementById('showVatRow'),
  formatNumbers: document.getElementById('formatNumbers'),
  aggregateTotal: document.getElementById('aggregateTotal'),
  paymentTimes: document.getElementById('paymentTimes'),
  paymentYear: document.getElementById('paymentYear'),
  paymentYearWrap: document.getElementById('paymentYearWrap'),
  autoPaymentAmounts: document.getElementById('autoPaymentAmounts'),
  paymentEditor: document.getElementById('paymentEditor'),
  transportNote: document.getElementById('transportNote'),
  closingText: document.getElementById('closingText'),
  showTransportNote: document.getElementById('showTransportNote'),
  showClosing: document.getElementById('showClosing'),
  quoteFontFamily: document.getElementById('quoteFontFamily'),
  quoteFontSize: document.getElementById('quoteFontSize'),
  quoteFontSizeValue: document.getElementById('quoteFontSizeValue'),
  quoteAccentColor: document.getElementById('quoteAccentColor'),
  enableAccentColor: document.getElementById('enableAccentColor'),
  quoteTextColor: document.getElementById('quoteTextColor'),
  enableTextColor: document.getElementById('enableTextColor'),
  quotePreview: document.getElementById('quotePreview'),
  pageCount: document.getElementById('pageCount'),
  serviceRowTemplate: document.getElementById('serviceRowTemplate'),
  paymentRowTemplate: document.getElementById('paymentRowTemplate')
};

const quoteFonts = {
  times: 'Times New Roman, Times, serif',
  arial: 'Arial, Tahoma, sans-serif',
  tahoma: 'Tahoma, Arial, sans-serif',
  georgia: 'Georgia, Times New Roman, serif'
};

let currentType = 'other';
const rowsByType = {
  other: TYPE_DEFAULTS.other.rows(),
  iso: TYPE_DEFAULTS.iso.rows(),
  inspection: TYPE_DEFAULTS.inspection.rows()
};
let haccpValues = ['', '', ''];
let payments = [];
let nextRowId = 1;
let nextPaymentId = 1;

function todayISO() {
  const d = new Date();
  const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
  return local.toISOString().slice(0, 10);
}

function escapeHTML(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function lineBreaks(value = '') {
  return escapeHTML(value).replace(/\n/g, '<br>');
}

function numericValue(value) {
  if (typeof value === 'number') return Number.isFinite(value) ? value : null;
  const cleaned = String(value ?? '').trim().replace(/,/g, '').replace(/\s+/g, '');
  if (!cleaned) return null;
  if (!/^[-+]?\d*\.?\d+$/.test(cleaned)) return null;
  const n = Number(cleaned);
  return Number.isFinite(n) ? n : null;
}

function money(value) {
  const n = Number(value || 0);
  if (!Number.isFinite(n)) return '0';
  const decimals = Number.isInteger(n) ? 0 : 2;
  if (els.formatNumbers?.checked) {
    return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: decimals });
  }
  return decimals ? n.toFixed(2).replace(/\.00$/, '') : String(n);
}

function displayCell(value) {
  const text = String(value ?? '').trim();
  if (!text) return '&nbsp;';
  const n = numericValue(text);
  return n == null ? escapeHTML(text) : money(n);
}

function rowsForType(type = currentType) {
  return rowsByType[type];
}

function stageCount(type = currentType) {
  return type === 'other' ? 3 : type === 'iso' ? 4 : 2;
}

function subtotalColumns(rows = rowsForType(), count = stageCount()) {
  const totals = Array(count).fill(0);
  const has = Array(count).fill(false);
  rows.forEach(row => {
    for (let i = 0; i < count; i += 1) {
      const n = numericValue(row.values[i]);
      if (n != null) {
        totals[i] += n;
        has[i] = true;
      }
    }
  });
  return { totals, has };
}

function withVat(subtotals) {
  const rate = Math.max(0, Number(els.vatRate.value) || 0);
  const vat = subtotals.totals.map((value, i) => subtotals.has[i] && els.showVatRow.checked ? value * rate / 100 : 0);
  const total = subtotals.totals.map((value, i) => subtotals.has[i] ? value + vat[i] : 0);
  return { ...subtotals, vat, total, rate };
}

function primaryCalc() {
  return withVat(subtotalColumns());
}

function haccpCalc() {
  const base = { totals: [0, 0, 0], has: [false, false, false] };
  haccpValues.forEach((v, i) => {
    const n = numericValue(v);
    if (n != null) { base.totals[i] = n; base.has[i] = true; }
  });
  return withVat(base);
}

function aggregateGrandTotal() {
  const p = primaryCalc();
  let total = p.total.reduce((sum, n) => sum + n, 0);
  if (currentType === 'inspection') total += haccpCalc().total.reduce((sum, n) => sum + n, 0);
  return total;
}

function rowFieldLabels(type) {
  if (type === 'other') {
    return [
      i18n('services', 'Services'),
      i18n('required_1', 'Required 1'),
      i18n('required_2', 'Required 2'),
      i18n('required_3', 'Required 3'),
      i18n('remarks', 'Remarks')
    ];
  }
  if (type === 'iso') {
    return [
      i18n('standard_item', 'International Standard / Item'),
      i18n('certification', 'Certification'),
      i18n('surveillance_1', 'Surveillance 1'),
      i18n('surveillance_2', 'Surveillance 2'),
      i18n('recertification', 'Recertification'),
      i18n('remarks', 'Remarks')
    ];
  }
  return [
    i18n('inspection_service', 'Inspection service'),
    i18n('initial', 'Initial'),
    i18n('routen', 'Routen'),
    i18n('remarks', 'Remarks')
  ];
}

function addServiceRow(initial = null) {
  const type = currentType;
  const count = stageCount(type);
  const row = initial || { item: '', values: Array(count).fill(''), remarks: '' };
  row._id = row._id || nextRowId++;
  rowsByType[type].push(row);
  renderTableEditor();
  updateAll();
}

function removeServiceRow(id) {
  const rows = rowsForType();
  const index = rows.findIndex(r => r._id === id);
  if (index >= 0) rows.splice(index, 1);
  if (!rows.length) rows.push({ _id: nextRowId++, item: '', values: Array(stageCount()).fill(''), remarks: '' });
  renderTableEditor();
  updateAll();
}

function renderTableEditor() {
  const type = currentType;
  const labels = rowFieldLabels(type);
  const rows = rowsForType();
  rows.forEach(row => { row._id = row._id || nextRowId++; });
  els.tableEditor.innerHTML = '';

  rows.forEach(row => {
    const node = els.serviceRowTemplate.content.firstElementChild.cloneNode(true);
    const fields = node.querySelector('.service-row-fields');
    fields.classList.add(type);
    const values = [row.item, ...row.values, row.remarks];

    labels.forEach((labelText, index) => {
      const label = document.createElement('label');
      const span = document.createElement('span');
      const input = document.createElement('input');
      span.textContent = labelText;
      input.type = 'text';
      input.dir = 'ltr';
      input.value = values[index] ?? '';
      if (index > 0 && index < labels.length - 1) input.inputMode = 'decimal';
      input.addEventListener('input', () => {
        if (index === 0) row.item = input.value;
        else if (index === labels.length - 1) row.remarks = input.value;
        else row.values[index - 1] = input.value;
        updateAll(false);
      });
      label.append(span, input);
      fields.appendChild(label);
    });

    const removeBtn = node.querySelector('.remove-service-row');
    removeBtn.title = i18n('delete_service_row', 'حذف الصف');
    removeBtn.addEventListener('click', () => removeServiceRow(row._id));
    els.tableEditor.appendChild(node);
  });
}

function renderHaccpEditor() {
  els.haccpEditor.innerHTML = '';
  [i18n('year_1', '1st year'), i18n('year_2', '2nd year'), i18n('year_3', '3rd year')].forEach((labelText, index) => {
    const label = document.createElement('label');
    const span = document.createElement('span');
    const input = document.createElement('input');
    span.textContent = labelText;
    input.type = 'text';
    input.dir = 'ltr';
    input.inputMode = 'decimal';
    input.value = haccpValues[index] || '';
    input.addEventListener('input', () => { haccpValues[index] = input.value; updateAll(false); });
    label.append(span, input);
    els.haccpEditor.appendChild(label);
  });
}

function defaultPaymentsFor(type) {
  return TYPE_DEFAULTS[type].paymentDue.map((due, index) => ({
    _id: nextPaymentId++,
    percent: 50,
    amount: '',
    due
  }));
}

function addPayment(initial = {}) {
  payments.push({
    _id: nextPaymentId++,
    percent: Number(initial.percent ?? 0),
    amount: initial.amount ?? '',
    due: initial.due ?? ''
  });
  renderPaymentEditor();
  updateAll();
}

function removePayment(id) {
  payments = payments.filter(p => p._id !== id);
  if (!payments.length) payments = defaultPaymentsFor(currentType);
  renderPaymentEditor();
  updateAll();
}

function renderPaymentEditor() {
  els.paymentEditor.innerHTML = '';
  payments.forEach(payment => {
    const node = els.paymentRowTemplate.content.firstElementChild.cloneNode(true);
    const percent = node.querySelector('.payment-percent');
    const amount = node.querySelector('.payment-amount');
    const due = node.querySelector('.payment-due');
    const percentLabel = percent.closest('label')?.querySelector('span');
    if (percentLabel) percentLabel.textContent = i18n('payment_percent_label', 'Payment %');
    const amountLabel = amount.closest('label')?.querySelector('span');
    if (amountLabel) amountLabel.textContent = i18n('payment_amount_label', 'Amount');
    const dueLabel = due.closest('label')?.querySelector('span');
    if (dueLabel) dueLabel.textContent = i18n('payment_due_label', 'Due to');
    percent.value = payment.percent;
    amount.value = payment.amount;
    due.value = payment.due;
    amount.disabled = els.autoPaymentAmounts.checked;
    if (els.autoPaymentAmounts.checked) {
      const total = aggregateGrandTotal();
      amount.placeholder = total ? money(total * Number(payment.percent || 0) / 100) : '';
    } else {
      amount.placeholder = '';
    }
    percent.addEventListener('input', () => { payment.percent = Math.max(0, Number(percent.value) || 0); updateAll(false); });
    amount.addEventListener('input', () => { payment.amount = amount.value; updateAll(false); });
    due.addEventListener('input', () => { payment.due = due.value; updateAll(false); });
    const removePayBtn = node.querySelector('.remove-payment-row');
    removePayBtn.title = i18n('delete_payment_row', 'حذف الدفعة');
    removePayBtn.addEventListener('click', () => removePayment(payment._id));
    els.paymentEditor.appendChild(node);
  });
}

function typographyAttrs() {
  const scale = Math.min(1.15, Math.max(.85, Number(els.quoteFontSize.value || 100) / 100));
  const font = quoteFonts[els.quoteFontFamily.value] || quoteFonts.times;
  const text = els.enableTextColor.checked ? els.quoteTextColor.value : '#000000';
  const accent = els.enableAccentColor.checked ? els.quoteAccentColor.value : '#4472c4';
  const closing = els.enableAccentColor.checked ? els.quoteAccentColor.value : '#0070c0';
  return `--quote-font:${font};--quote-scale:${scale};--quote-text:${text};--quote-accent:${accent};--quote-closing:${closing};`;
}

function tableRowsHTML(type, calc) {
  return rowsForType(type).map((row, index) => {
    const cells = row.values.map(v => `<td class="value-cell">${displayCell(v)}</td>`).join('');
    return `<tr><td>${index + 1}</td><td class="left-cell">${displayCell(row.item)}</td>${cells}<td>${displayCell(row.remarks)}</td></tr>`;
  }).join('');
}

function vatAndTotalRows(type, calc) {
  const valueCells = (values, has) => values.map((v, i) => `<td class="value-cell">${has[i] ? money(v) : '&nbsp;'}</td>`).join('');
  const vatCells = valueCells(calc.vat, calc.has);
  const totalCells = valueCells(calc.total, calc.has);
  if (type === 'iso') {
    return `${els.showVatRow.checked ? `<tr class="vat-row"><td colspan="2">Add Value Tax</td>${vatCells}<td>&nbsp;</td></tr>` : ''}
      <tr class="total-row"><td colspan="2">Total</td>${totalCells}<td>&nbsp;</td></tr>
      <tr class="iso-payment-time"><td colspan="2">Payment time</td><td>1<sup>st</sup> year</td><td>2<sup>nd</sup> year</td><td>3<sup>rd</sup> year</td><td>4<sup>th</sup> year</td><td>&nbsp;</td></tr>`;
  }
  return `${els.showVatRow.checked ? `<tr class="vat-row"><td>&nbsp;</td><td class="left-cell">Add Value Tax</td>${vatCells}<td>&nbsp;</td></tr>` : ''}
    <tr class="total-row"><td>&nbsp;</td><td class="left-cell">Total</td>${totalCells}<td>&nbsp;</td></tr>`;
}

function mainTableHTML(type) {
  const calc = primaryCalc();
  const rows = tableRowsHTML(type, calc);
  if (type === 'other') {
    return `<table class="mpc-table main-table">
      <colgroup><col class="c-sr"><col class="c-item"><col class="c-r1"><col class="c-r2"><col class="c-r3"><col class="c-remarks"></colgroup>
      <thead><tr><th>Sr.#</th><th>Services</th><th colspan="3">Required Services</th><th>Remarks</th></tr></thead>
      <tbody>${rows}${vatAndTotalRows(type, calc)}</tbody>
    </table>`;
  }
  if (type === 'iso') {
    return `<table class="mpc-table main-table">
      <colgroup><col class="c-sr"><col class="c-item"><col class="c-stage"><col class="c-stage"><col class="c-stage"><col class="c-stage"><col class="c-remarks"></colgroup>
      <thead>
        <tr class="iso-head-top"><th rowspan="2">Sr#</th><th rowspan="2">International<br>Standard / Item</th><th colspan="4">Required Services</th><th rowspan="2">Remarks</th></tr>
        <tr class="iso-head-sub"><th>Certification</th><th>Surveillance1</th><th>Surveillance<br>2</th><th>Recertification</th></tr>
      </thead>
      <tbody>${rows}${vatAndTotalRows(type, calc)}</tbody>
    </table>`;
  }
  return `<table class="mpc-table main-table">
    <colgroup><col class="c-sr"><col class="c-item"><col class="c-initial"><col class="c-routen"><col class="c-remarks"></colgroup>
    <thead>
      <tr><th>Sr.#</th><th rowspan="2">Inspection services</th><th colspan="2">Required Services</th><th>Remarks</th></tr>
      <tr><th>&nbsp;</th><th>Initial</th><th>Routen</th><th>&nbsp;</th></tr>
    </thead>
    <tbody>${rows}${vatAndTotalRows(type, calc)}</tbody>
  </table>`;
}

function haccpTableHTML() {
  const calc = haccpCalc();
  const vals = haccpValues.map(v => `<td class="value-cell">${displayCell(v)}</td>`).join('');
  const vat = calc.vat.map((v, i) => `<td class="value-cell">${calc.has[i] ? money(v) : '&nbsp;'}</td>`).join('');
  const total = calc.total.map((v, i) => `<td class="value-cell">${calc.has[i] ? money(v) : '&nbsp;'}</td>`).join('');
  return `<table class="mpc-table haccp-table">
    <colgroup><col class="c-haccp-label"><col class="c-year"><col class="c-year"><col class="c-year"><col class="c-blank1"><col class="c-blank2"></colgroup>
    <thead><tr><th>HACCP Certificate</th><th class="year-head">1<sup>st</sup> year</th><th class="year-head">2<sup>nd</sup> year</th><th class="year-head">3<sup>rd</sup> year</th><th>&nbsp;</th><th>&nbsp;</th></tr></thead>
    <tbody>
      <tr><td>HACCP</td>${vals}<td>&nbsp;</td><td>&nbsp;</td></tr>
      ${els.showVatRow.checked ? `<tr><td>Add Value Tax</td>${vat}<td>&nbsp;</td><td>&nbsp;</td></tr>` : ''}
      <tr><td>Total</td>${total}<td>&nbsp;</td><td>&nbsp;</td></tr>
    </tbody>
  </table>`;
}

function paymentSentenceHTML() {
  const count = escapeHTML(els.paymentTimes.value || '02');
  if (currentType === 'other') return `Payment will be in (<span class="payment-count">${count}</span>) times`;
  const year = String(els.paymentYear.value || '').trim();
  const yearPart = year ? ` for ${escapeHTML(year)} year` : ' for…….year';
  return `Payment will be in (<span class="payment-count">${count}</span>) times${yearPart}${currentType === 'inspection' ? '.' : ''}`;
}

function paymentAmountHTML(payment) {
  if (els.autoPaymentAmounts.checked) {
    const total = aggregateGrandTotal();
    if (!total) return '&nbsp;';
    return money(total * Number(payment.percent || 0) / 100);
  }
  return displayCell(payment.amount);
}

function paymentTableHTML() {
  const rows = payments.map(payment => `<tr>
    <td>${money(payment.percent)}%</td>
    <td class="amount-cell value-cell">${paymentAmountHTML(payment)}</td>
    <td>${displayCell(payment.due)}</td>
  </tr>`).join('');
  return `<table class="payment-table">
    <colgroup><col class="c-pay"><col class="c-amount"><col class="c-due"></colgroup>
    <thead><tr><th>Payment #</th><th class="amount-cell">Amount</th><th>Due to</th></tr></thead>
    <tbody>${rows}</tbody>
  </table>`;
}

function buildPreview() {
  const type = currentType;
  const defaults = TYPE_DEFAULTS[type];
  const rowCount = rowsForType().length + payments.length + (type === 'inspection' ? 4 : 0);
  const densityClass = rowCount >= 13 ? 'extra-dense' : rowCount >= 10 ? 'dense' : '';
  const title = escapeHTML(els.offerTitle.value || defaults.title);
  const client = String(els.clientName.value || '').trim();
  const dear = client ? `Dear ${escapeHTML(client)}` : 'Dear ……………………..';
  const transport = String(els.transportNote.value || '').trim();
  const closing = String(els.closingText.value || '').trim();
  const haccp = type === 'inspection' ? haccpTableHTML() : '';

  els.quotePreview.innerHTML = `<section class="quote-page type-${type} ${densityClass}" style="${typographyAttrs()}">
    <img class="mpc-page-bg" src="${defaults.bg}" alt="" />
    <div class="mpc-content">
      <h1 class="offer-title">${title}</h1>
      <div class="mpc-text-width dear-line">${dear}</div>
      <p class="mpc-text-width intro-copy">${lineBreaks(els.introText.value || defaults.intro)}</p>
      <p class="mpc-text-width lead-copy">${escapeHTML(els.leadText.value || defaults.lead)}</p>
      <div class="financial-zone">${mainTableHTML(type)}${haccp}</div>
      <section class="payment-section">
        <div class="payment-heading">Payment Method:</div>
        <div class="payment-sentence">${paymentSentenceHTML()}</div>
        ${paymentTableHTML()}
      </section>
      ${els.showTransportNote.checked && transport ? `<div class="transport-note"><span class="transport-star">*</span>${lineBreaks(transport)}</div>` : ''}
      ${els.showClosing.checked && closing ? `<div class="closing">${lineBreaks(closing)}</div>` : ''}
    </div>
  </section>`;
  els.pageCount.textContent = i18n('page_count', 'عدد الصفحات: 1');
}

function updateAll(refreshEditors = true) {
  els.aggregateTotal.textContent = money(aggregateGrandTotal());
  if (refreshEditors) renderPaymentEditor();
  buildPreview();
}

function applyType(type, resetText = true) {
  currentType = type;
  const d = TYPE_DEFAULTS[type];
  if (resetText) {
    els.offerTitle.value = d.title;
    els.introText.value = d.intro;
    els.leadText.value = d.lead;
    els.paymentYear.value = '';
    payments = defaultPaymentsFor(type);
  }
  els.haccpPanel.hidden = type !== 'inspection';
  els.paymentYearWrap.style.display = type === 'other' ? 'none' : 'block';
  renderTableEditor();
  renderHaccpEditor();
  renderPaymentEditor();
  updateAll(false);
}

function resetTypography() {
  els.quoteFontFamily.value = 'times';
  els.quoteFontSize.value = '100';
  els.enableAccentColor.checked = false;
  els.quoteAccentColor.value = '#4472c4';
  els.enableTextColor.checked = false;
  els.quoteTextColor.value = '#000000';
  els.quoteFontSizeValue.textContent = '100%';
  updateAll(false);
}

function resetForm() {
  if (!confirm('هل تريد مسح بيانات عرض السعر والبدء من جديد؟')) return;
  els.form.reset();
  els.quoteDate.value = todayISO();
  els.vatRate.value = '0';
  els.showVatRow.checked = true;
  els.formatNumbers.checked = true;
  els.showTransportNote.checked = true;
  els.showClosing.checked = true;
  els.transportNote.value = 'Transportation is not included in the offer, and accommodation is not included if required.';
  els.closingText.value = 'Thanks, and regards';
  rowsByType.other = TYPE_DEFAULTS.other.rows();
  rowsByType.iso = TYPE_DEFAULTS.iso.rows();
  rowsByType.inspection = TYPE_DEFAULTS.inspection.rows();
  haccpValues = ['', '', ''];
  els.offerType.value = 'other';
  resetTypography();
  applyType('other', true);
}

function loadDemo() {
  els.clientName.value = 'ABC Company';
  els.quotationNo.value = 'MPC-2026-001';
  els.quoteDate.value = todayISO();
  els.vatRate.value = '14';
  els.offerType.value = 'inspection';
  rowsByType.inspection = [
    { item: 'FS Inspection', values: ['4500', '2500'], remarks: 'Per visit' },
    { item: 'Labeling', values: ['1800', '1200'], remarks: '' },
    { item: 'COC', values: ['3000', ''], remarks: '' }
  ];
  haccpValues = ['6500', '3500', '3500'];
  applyType('inspection', true);
  els.paymentYear.value = '1st';
  updateAll();
}

els.quoteDate.value = todayISO();


/* ===== PDF / Word export engine (offline, client-side) ===== */
function safeFilePart(value) {
  return String(value || '')
    .trim()
    .replace(/[\\/:*?"<>|]+/g, '-')
    .replace(/\s+/g, ' ')
    .slice(0, 70) || 'Quotation';
}

function collectDocumentCss() {
  let css = '';
  Array.from(document.styleSheets).forEach((sheet) => {
    try {
      Array.from(sheet.cssRules || []).forEach((rule) => { css += `${rule.cssText}\n`; });
    } catch (error) {
      // Same-origin stylesheet. Ignore any inaccessible optional sheet.
    }
  });
  return css;
}

function embeddedImageSource(src) {
  const value = String(src || '');
  if (!value) return '';
  if (value.startsWith('data:')) return value;

  // Handle normal project-relative asset paths even when the document is
  // opened from file:// or rendered in an about:blank print iframe.
  const normalized = value.replace(/^\.\//, '').split(/[?#]/)[0];
  if (window.MPC_ASSET_DATA && window.MPC_ASSET_DATA[normalized]) {
    return window.MPC_ASSET_DATA[normalized];
  }
  const directName = decodeURIComponent(normalized.split('/').pop() || '');
  const directKey = `assets/${directName}`;
  if (window.MPC_ASSET_DATA && window.MPC_ASSET_DATA[directKey]) {
    return window.MPC_ASSET_DATA[directKey];
  }

  try {
    const parsed = new URL(value, document.baseURI);
    const fileName = decodeURIComponent(parsed.pathname.split('/').pop() || '');
    const key = `assets/${fileName}`;
    return (window.MPC_ASSET_DATA && window.MPC_ASSET_DATA[key]) || value;
  } catch (error) {
    return value;
  }
}

function quotationHtmlWithEmbeddedImages() {
  const clone = els.quotePreview.cloneNode(true);
  const sourceImages = Array.from(els.quotePreview.querySelectorAll('img'));
  const clonedImages = Array.from(clone.querySelectorAll('img'));
  clonedImages.forEach((img, index) => {
    const source = sourceImages[index];
    const embedded = embeddedImageSource(source?.getAttribute('src') || source?.src || img.getAttribute('src'));
    if (embedded) img.setAttribute('src', embedded);
  });
  return clone.innerHTML;
}

function printQuote() {
  buildPreview();

  // Print the SAME quotation DOM that the employee is previewing.
  // Previous iframe cloning could lose CSS/media-query state and image sizing.
  // Printing in-place is more reliable because Chrome/Edge use the exact
  // computed layout already visible on screen.
  const body = document.body;
  const oldTitle = document.title;
  const exportTitle = `${safeFilePart(els.quotationNo.value || 'Quotation')} - ${safeFilePart(els.clientName.value || 'Client')}`;

  const cleanup = () => {
    body.classList.remove('mpc-printing');
    document.documentElement.classList.remove('mpc-printing');
    document.title = oldTitle;
  };

  body.classList.add('mpc-printing');
  document.documentElement.classList.add('mpc-printing');
  document.title = exportTitle;

  window.addEventListener('afterprint', cleanup, { once: true });

  // Two animation frames make sure the final print-only layout has been
  // applied before Chrome/Edge takes its print snapshot.
  requestAnimationFrame(() => requestAnimationFrame(() => {
    try {
      window.print();
    } catch (error) {
      console.error('Print failed:', error);
      cleanup();
      alert('تعذر فتح نافذة الطباعة. حاول مرة أخرى من Chrome أو Edge.');
    }
  }));

  // Safety cleanup for browsers that do not fire afterprint reliably.
  setTimeout(() => {
    if (body.classList.contains('mpc-printing') && !(window.matchMedia && window.matchMedia('print').matches)) cleanup();
  }, 15000);
}

function xmlEscape(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

function svgDataUrl(svgText) {
  const bytes = new TextEncoder().encode(svgText);
  let binary = '';
  const chunk = 0x8000;
  for (let i = 0; i < bytes.length; i += chunk) {
    binary += String.fromCharCode(...bytes.subarray(i, i + chunk));
  }
  return `data:image/svg+xml;base64,${btoa(binary)}`;
}

const RASTER_STYLE_PROPS = [
  'display','position','box-sizing','top','right','bottom','left','z-index',
  'width','height','min-width','min-height','max-width','max-height',
  'margin-top','margin-right','margin-bottom','margin-left',
  'padding-top','padding-right','padding-bottom','padding-left',
  'border-top-width','border-right-width','border-bottom-width','border-left-width',
  'border-top-style','border-right-style','border-bottom-style','border-left-style',
  'border-top-color','border-right-color','border-bottom-color','border-left-color',
  'border-top-left-radius','border-top-right-radius','border-bottom-right-radius','border-bottom-left-radius',
  'border-collapse','border-spacing','table-layout',
  'background-color','background-image','background-size','background-position','background-repeat','background-clip','background-origin',
  'color','opacity','visibility','overflow','overflow-x','overflow-y','clip-path',
  'font-family','font-size','font-weight','font-style','font-variant','line-height','letter-spacing','word-spacing',
  'text-align','text-align-last','text-decoration-line','text-decoration-color','text-decoration-style','text-decoration-thickness',
  'text-underline-offset','text-transform','text-indent','text-shadow','white-space','overflow-wrap','word-break','direction','unicode-bidi','vertical-align',
  'object-fit','object-position',
  'box-shadow','transform','transform-origin','filter',
  'list-style-type','list-style-position','list-style-image',
  'flex','flex-basis','flex-grow','flex-shrink','flex-direction','flex-wrap','align-items','align-content','align-self','justify-content','justify-items','justify-self','order',
  'grid-template-columns','grid-template-rows','grid-column','grid-row','grid-auto-flow','grid-auto-columns','grid-auto-rows','column-gap','row-gap','gap','place-items','place-content'
];

function copyRasterStyle(source, target, pseudo = null) {
  const style = getComputedStyle(source, pseudo);
  RASTER_STYLE_PROPS.forEach((prop) => {
    const value = style.getPropertyValue(prop);
    if (value) target.style.setProperty(prop, value);
  });
}

function pseudoText(content) {
  if (!content || content === 'none' || content === 'normal' || content === '""' || content === "''") return '';
  let text = content;
  if ((text.startsWith('"') && text.endsWith('"')) || (text.startsWith("'") && text.endsWith("'"))) text = text.slice(1, -1);
  return text.replace(/\\A/g, '\n').replace(/\\(["'\\])/g, '$1');
}

function materializePseudo(source, clone, pseudo) {
  const computed = getComputedStyle(source, pseudo);
  const text = pseudoText(computed.content);
  if (!text) return;
  const span = document.createElement('span');
  span.textContent = text;
  span.setAttribute('aria-hidden', 'true');
  copyRasterStyle(source, span, pseudo);
  // Pseudo elements do not participate as real DOM nodes; this materializes
  // the few bullets/dashes used by the quotation template for raster export.
  if (pseudo === '::before') clone.insertBefore(span, clone.firstChild);
  else clone.appendChild(span);
}

function clonePageWithComputedStyles(page) {
  const clone = page.cloneNode(true);
  const sourceNodes = [page, ...page.querySelectorAll('*')];
  const clonedNodes = [clone, ...clone.querySelectorAll('*')];

  for (let i = 0; i < sourceNodes.length; i += 1) {
    const source = sourceNodes[i];
    const target = clonedNodes[i];
    if (!target) continue;
    copyRasterStyle(source, target);

    if (source.tagName === 'IMG') {
      const embedded = embeddedImageSource(source.getAttribute('src') || source.src);
      if (embedded) target.setAttribute('src', embedded);
      target.removeAttribute('srcset');
      target.removeAttribute('loading');

      // Product photos use intrinsic dimensions constrained by max-width /
      // max-height. If the live <img> has not finished loading yet, its
      // computed width can collapse to the broken-image icon size. Let the
      // embedded data URI provide its real intrinsic size during SVG render.
      if (source.closest('.product-card')) {
        target.style.setProperty('width', 'auto', 'important');
        target.style.setProperty('height', 'auto', 'important');
        target.style.setProperty('max-width', '120mm', 'important');
        target.style.setProperty('max-height', '75mm', 'important');
        target.style.setProperty('object-fit', 'contain', 'important');
      }
    }
  }

  // Materialize the quotation's CSS-generated dashes/bullets after the main
  // node walk so the source/clone indexes remain aligned.
  for (let i = 0; i < sourceNodes.length; i += 1) {
    const source = sourceNodes[i];
    const target = clonedNodes[i];
    if (!target || target.nodeType !== 1) continue;
    materializePseudo(source, target, '::before');
    materializePseudo(source, target, '::after');
  }

  clone.style.setProperty('zoom', '1', 'important');
  clone.style.setProperty('transform', 'none', 'important');
  clone.style.setProperty('transform-origin', 'top left', 'important');
  clone.style.setProperty('margin', '0', 'important');
  clone.style.setProperty('box-shadow', 'none', 'important');
  clone.style.setProperty('width', '210mm', 'important');
  clone.style.setProperty('height', '297mm', 'important');
  clone.style.setProperty('min-height', '297mm', 'important');
  clone.style.setProperty('max-height', '297mm', 'important');
  clone.style.setProperty('overflow', 'hidden', 'important');
  clone.style.setProperty('background', '#fff', 'important');
  return clone;
}

async function quotePageToPng(page, scale = 1.8) {
  // Rasterize the exact computed page, not a fresh CSS clone. This avoids the
  // CSS/iframe/foreignObject differences that previously enlarged logos and
  // dropped the red/black header/footer in Word exports.
  const clone = clonePageWithComputedStyles(page);

  const width = 794;
  const height = 1123;
  const serialized = new XMLSerializer().serializeToString(clone);
  const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
  <foreignObject x="0" y="0" width="${width}" height="${height}">
    <div xmlns="http://www.w3.org/1999/xhtml" style="width:${width}px;height:${height}px;margin:0;padding:0;background:#fff;overflow:hidden;">
      ${serialized}
    </div>
  </foreignObject>
</svg>`;

  const image = new Image();
  const svgUrl = svgDataUrl(svg);
  await new Promise((resolve, reject) => {
    image.onload = resolve;
    image.onerror = () => reject(new Error('تعذر تجهيز صفحة عرض السعر للتصدير.'));
    image.src = svgUrl;
  });

  const canvas = document.createElement('canvas');
  canvas.width = Math.round(width * scale);
  canvas.height = Math.round(height * scale);
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.setTransform(scale, 0, 0, scale, 0, 0);
  ctx.drawImage(image, 0, 0, width, height);
  return canvas.toDataURL('image/png');
}

function dataUrlToBytes(dataUrl) {
  const base64 = String(dataUrl).split(',')[1] || '';
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i += 1) bytes[i] = binary.charCodeAt(i);
  return bytes;
}

const CRC32_TABLE = (() => {
  const table = new Uint32Array(256);
  for (let n = 0; n < 256; n += 1) {
    let c = n;
    for (let k = 0; k < 8; k += 1) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
    table[n] = c >>> 0;
  }
  return table;
})();

function crc32(bytes) {
  let c = 0xFFFFFFFF;
  for (let i = 0; i < bytes.length; i += 1) c = CRC32_TABLE[(c ^ bytes[i]) & 0xFF] ^ (c >>> 8);
  return (c ^ 0xFFFFFFFF) >>> 0;
}

function concatBytes(parts) {
  const length = parts.reduce((sum, part) => sum + part.length, 0);
  const out = new Uint8Array(length);
  let offset = 0;
  parts.forEach((part) => { out.set(part, offset); offset += part.length; });
  return out;
}

function zipStored(files) {
  const encoder = new TextEncoder();
  const localParts = [];
  const centralParts = [];
  let localOffset = 0;
  const now = new Date();
  const dosTime = ((now.getHours() & 31) << 11) | ((now.getMinutes() & 63) << 5) | ((Math.floor(now.getSeconds() / 2)) & 31);
  const dosDate = (((Math.max(now.getFullYear(), 1980) - 1980) & 127) << 9) | (((now.getMonth() + 1) & 15) << 5) | (now.getDate() & 31);

  files.forEach(({ name, data }) => {
    const nameBytes = encoder.encode(name);
    const bytes = typeof data === 'string' ? encoder.encode(data) : data;
    const crc = crc32(bytes);

    const local = new Uint8Array(30 + nameBytes.length);
    const lv = new DataView(local.buffer);
    lv.setUint32(0, 0x04034b50, true);
    lv.setUint16(4, 20, true);
    lv.setUint16(6, 0x0800, true);
    lv.setUint16(8, 0, true);
    lv.setUint16(10, dosTime, true);
    lv.setUint16(12, dosDate, true);
    lv.setUint32(14, crc, true);
    lv.setUint32(18, bytes.length, true);
    lv.setUint32(22, bytes.length, true);
    lv.setUint16(26, nameBytes.length, true);
    lv.setUint16(28, 0, true);
    local.set(nameBytes, 30);
    localParts.push(local, bytes);

    const central = new Uint8Array(46 + nameBytes.length);
    const cv = new DataView(central.buffer);
    cv.setUint32(0, 0x02014b50, true);
    cv.setUint16(4, 20, true);
    cv.setUint16(6, 20, true);
    cv.setUint16(8, 0x0800, true);
    cv.setUint16(10, 0, true);
    cv.setUint16(12, dosTime, true);
    cv.setUint16(14, dosDate, true);
    cv.setUint32(16, crc, true);
    cv.setUint32(20, bytes.length, true);
    cv.setUint32(24, bytes.length, true);
    cv.setUint16(28, nameBytes.length, true);
    cv.setUint16(30, 0, true);
    cv.setUint16(32, 0, true);
    cv.setUint16(34, 0, true);
    cv.setUint16(36, 0, true);
    cv.setUint32(38, 0, true);
    cv.setUint32(42, localOffset, true);
    central.set(nameBytes, 46);
    centralParts.push(central);

    localOffset += local.length + bytes.length;
  });

  const centralData = concatBytes(centralParts);
  const eocd = new Uint8Array(22);
  const ev = new DataView(eocd.buffer);
  ev.setUint32(0, 0x06054b50, true);
  ev.setUint16(4, 0, true);
  ev.setUint16(6, 0, true);
  ev.setUint16(8, files.length, true);
  ev.setUint16(10, files.length, true);
  ev.setUint32(12, centralData.length, true);
  ev.setUint32(16, localOffset, true);
  ev.setUint16(20, 0, true);
  return concatBytes([...localParts, centralData, eocd]);
}

function buildDocx(pagePngDataUrls) {
  const pageW = 7560310;
  const pageH = 10692130;
  const rels = pagePngDataUrls.map((_, i) =>
    `<Relationship Id="rId${i + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/page${i + 1}.png"/>`
  ).join('');

  const pageXml = pagePngDataUrls.map((_, i) => {
    const id = i + 1;
    const pageBreak = i < pagePngDataUrls.length - 1
      ? '<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>'
      : '';
    return `<w:p>
      <w:pPr><w:spacing w:before="0" w:after="0" w:line="1" w:lineRule="exact"/></w:pPr>
      <w:r><w:drawing>
        <wp:anchor distT="0" distB="0" distL="0" distR="0" simplePos="0" relativeHeight="0" behindDoc="0" locked="1" layoutInCell="1" allowOverlap="1">
          <wp:simplePos x="0" y="0"/>
          <wp:positionH relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionH>
          <wp:positionV relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionV>
          <wp:extent cx="${pageW}" cy="${pageH}"/>
          <wp:effectExtent l="0" t="0" r="0" b="0"/>
          <wp:wrapNone/>
          <wp:docPr id="${id}" name="Quotation Page ${id}"/>
          <wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>
          <a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
            <pic:pic>
              <pic:nvPicPr><pic:cNvPr id="${id}" name="page${id}.png"/><pic:cNvPicPr/></pic:nvPicPr>
              <pic:blipFill><a:blip r:embed="rId${id}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>
              <pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="${pageW}" cy="${pageH}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>
            </pic:pic>
          </a:graphicData></a:graphic>
        </wp:anchor>
      </w:drawing></w:r>
    </w:p>${pageBreak}`;
  }).join('');

  const documentXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
 xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
 xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
 xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
 <w:body>${pageXml}
  <w:sectPr>
    <w:pgSz w:w="11906" w:h="16838"/>
    <w:pgMar w:top="0" w:right="0" w:bottom="0" w:left="0" w:header="0" w:footer="0" w:gutter="0"/>
  </w:sectPr>
 </w:body>
</w:document>`;

  const contentTypes = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="xml" ContentType="application/xml"/>
 <Default Extension="png" ContentType="image/png"/>
 <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
 <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
 <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>`;
  const rootRels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
 <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
 <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>`;
  const documentRels = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${rels}</Relationships>`;
  const now = new Date().toISOString();
  const core = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
 <dc:title>${xmlEscape(els.quotationNo.value || 'Quotation')}</dc:title>
 <dc:creator>Monitoring of Pioneer Conformity</dc:creator>
 <dcterms:created xsi:type="dcterms:W3CDTF">${now}</dcterms:created>
 <dcterms:modified xsi:type="dcterms:W3CDTF">${now}</dcterms:modified>
</cp:coreProperties>`;
  const app = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
 <Application>Monitoring of Pioneer Conformity Quotation Generator</Application>
 <Pages>${pagePngDataUrls.length}</Pages>
</Properties>`;

  const files = [
    { name: '[Content_Types].xml', data: contentTypes },
    { name: '_rels/.rels', data: rootRels },
    { name: 'word/document.xml', data: documentXml },
    { name: 'word/_rels/document.xml.rels', data: documentRels },
    { name: 'docProps/core.xml', data: core },
    { name: 'docProps/app.xml', data: app },
    ...pagePngDataUrls.map((url, i) => ({ name: `word/media/page${i + 1}.png`, data: dataUrlToBytes(url) }))
  ];
  return zipStored(files);
}

async function exportWord() {
  buildPreview();
  const pages = Array.from(els.quotePreview.querySelectorAll('.quote-page'));
  if (!pages.length) {
    alert('لا توجد صفحات لتصديرها.');
    return;
  }

  const wordButtons = [document.getElementById('wordBtn'), document.getElementById('wordTopBtn')].filter(Boolean);
  const labels = wordButtons.map(btn => btn.textContent);
  wordButtons.forEach(btn => { btn.disabled = true; btn.textContent = 'جارٍ تجهيز Word...'; });

  try {
    const images = [];
    for (let i = 0; i < pages.length; i += 1) {
      wordButtons.forEach(btn => { btn.textContent = `Word ${i + 1}/${pages.length}`; });
      images.push(await quotePageToPng(pages[i]));
    }

    const bytes = buildDocx(images);
    const blob = new Blob([bytes], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${safeFilePart(els.quotationNo.value || 'Quotation')} - ${safeFilePart(els.clientName.value || 'Client')}.docx`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2500);
  } catch (error) {
    console.error('Word export failed:', error);
    alert(`تعذر إنشاء ملف Word. ${error?.message || 'حاول مرة أخرى.'}`);
  } finally {
    wordButtons.forEach((btn, i) => { btn.disabled = false; btn.textContent = labels[i]; });
  }
}

/* ===== CRM persistence (Save / Update / Reopen) ===== */
function serializePayload() {
  const type = currentType;
  return {
    schema_version: 4,
    generator: 'mpc',
    generator_version: '4.0',
    offerType: type,
    clientName: els.clientName.value.trim(),
    quotationNo: els.quotationNo.value.trim(),
    quoteDate: els.quoteDate.value || todayISO(),
    vatRate: Number(els.vatRate.value) || 0,
    offerTitle: els.offerTitle.value,
    introText: els.introText.value,
    leadText: els.leadText.value,
    rows: rowsForType(type).map(r => ({
      item: r.item || '',
      values: Array.isArray(r.values) ? [...r.values] : [],
      remarks: r.remarks || ''
    })),
    haccpValues: [...haccpValues],
    showVatRow: els.showVatRow.checked,
    formatNumbers: els.formatNumbers.checked,
    paymentTimes: els.paymentTimes.value,
    paymentYear: els.paymentYear.value,
    autoPaymentAmounts: els.autoPaymentAmounts.checked,
    payments: payments.map(p => ({
      percent: Number(p.percent) || 0,
      amount: String(p.amount ?? ''),
      due: String(p.due ?? '')
    })),
    transportNote: els.transportNote.value,
    closingText: els.closingText.value,
    showTransportNote: els.showTransportNote.checked,
    showClosing: els.showClosing.checked,
    typography: {
      quoteFontFamily: els.quoteFontFamily.value,
      quoteFontSize: Number(els.quoteFontSize.value) || 100,
      enableAccentColor: els.enableAccentColor.checked,
      quoteAccentColor: els.quoteAccentColor.value,
      enableTextColor: els.enableTextColor.checked,
      quoteTextColor: els.quoteTextColor.value
    }
  };
}

function populateFromPayload(data) {
  if (!data || typeof data !== 'object') return;
  const type = data.offerType || 'other';
  currentType = type;
  els.offerType.value = type;

  if (typeof data.clientName !== 'undefined') els.clientName.value = data.clientName;
  if (typeof data.quotationNo !== 'undefined') els.quotationNo.value = data.quotationNo;
  if (typeof data.quoteDate !== 'undefined') els.quoteDate.value = data.quoteDate;
  if (typeof data.vatRate !== 'undefined') els.vatRate.value = data.vatRate;
  if (typeof data.offerTitle !== 'undefined') els.offerTitle.value = data.offerTitle;
  if (typeof data.introText !== 'undefined') els.introText.value = data.introText;
  if (typeof data.leadText !== 'undefined') els.leadText.value = data.leadText;
  if (typeof data.showVatRow !== 'undefined') els.showVatRow.checked = !!data.showVatRow;
  if (typeof data.formatNumbers !== 'undefined') els.formatNumbers.checked = !!data.formatNumbers;
  if (typeof data.paymentTimes !== 'undefined') els.paymentTimes.value = data.paymentTimes;
  if (typeof data.paymentYear !== 'undefined') els.paymentYear.value = data.paymentYear;
  if (typeof data.autoPaymentAmounts !== 'undefined') els.autoPaymentAmounts.checked = !!data.autoPaymentAmounts;
  if (typeof data.transportNote !== 'undefined') els.transportNote.value = data.transportNote;
  if (typeof data.closingText !== 'undefined') els.closingText.value = data.closingText;
  if (typeof data.showTransportNote !== 'undefined') els.showTransportNote.checked = !!data.showTransportNote;
  if (typeof data.showClosing !== 'undefined') els.showClosing.checked = !!data.showClosing;

  if (data.rows && Array.isArray(data.rows)) {
    rowsByType[type] = data.rows.map(r => ({
      _id: nextRowId++,
      item: r.item || '',
      values: Array.isArray(r.values) ? [...r.values] : Array(stageCount(type)).fill(''),
      remarks: r.remarks || ''
    }));
  }

  if (data.haccpValues && Array.isArray(data.haccpValues)) {
    haccpValues = [...data.haccpValues];
  }

  if (data.payments && Array.isArray(data.payments)) {
    payments = data.payments.map(p => ({
      _id: nextPaymentId++,
      percent: Number(p.percent) || 0,
      amount: p.amount ?? '',
      due: p.due ?? ''
    }));
  }

  if (data.typography && typeof data.typography === 'object') {
    const t = data.typography;
    if (t.quoteFontFamily) els.quoteFontFamily.value = t.quoteFontFamily;
    if (typeof t.quoteFontSize !== 'undefined') {
      els.quoteFontSize.value = t.quoteFontSize;
      els.quoteFontSizeValue.textContent = `${t.quoteFontSize}%`;
    }
    if (typeof t.enableAccentColor !== 'undefined') els.enableAccentColor.checked = !!t.enableAccentColor;
    if (t.quoteAccentColor) els.quoteAccentColor.value = t.quoteAccentColor;
    if (typeof t.enableTextColor !== 'undefined') els.enableTextColor.checked = !!t.enableTextColor;
    if (t.quoteTextColor) els.quoteTextColor.value = t.quoteTextColor;
  }

  applyType(type, false);
  updateAll(true);
}

async function saveToCrm() {
  const saveBtn = document.getElementById('crmSaveQuotationBtn');
  const statusEl = document.getElementById('crmQuotationSaveStatus');

  if (!els.clientName.value.trim()) {
    alert(i18n('client_name_required', 'يرجى إدخال اسم العميل / الجهة قبل الحفظ.'));
    els.clientName.focus();
    return;
  }
  if (!els.quotationNo.value.trim()) {
    alert(i18n('quotation_no_required', 'يرجى إدخال رقم عرض السعر قبل الحفظ.'));
    els.quotationNo.focus();
    return;
  }

  const payload = serializePayload();
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  const isUpdate = !!window.CRM_QUOTATION_UPDATE_URL;
  const url = isUpdate ? window.CRM_QUOTATION_UPDATE_URL : window.CRM_QUOTATION_STORE_URL;
  const method = isUpdate ? 'PUT' : 'POST';

  if (!url) {
    console.error('No save/update URL configured');
    return;
  }

  if (saveBtn) {
    saveBtn.disabled = true;
    if (!saveBtn.dataset.originalText) saveBtn.dataset.originalText = saveBtn.textContent;
    saveBtn.textContent = i18n('saving', 'جاري الحفظ...');
  }
  if (statusEl) {
    statusEl.className = '';
    statusEl.textContent = i18n('saving', 'جاري الحفظ...');
  }

  try {
    const res = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken || ''
      },
      body: JSON.stringify(payload)
    });

    const result = await res.json().catch(() => null);

    if (!res.ok) {
      const errorMsg = result?.message || (result?.errors ? Object.values(result.errors).flat().join(' ') : 'فشل الحفظ.');
      throw new Error(errorMsg);
    }

    if (statusEl) {
      statusEl.className = 'ok';
      statusEl.textContent = result?.message || (isUpdate ? 'تم التحديث بنجاح' : 'تم الحفظ بنجاح');
    }

    if (!isUpdate && result?.redirect) {
      setTimeout(() => {
        window.location.href = result.redirect;
      }, 400);
    } else if (isUpdate) {
      if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.textContent = saveBtn.dataset.originalText || i18n('update_quotation', 'تحديث عرض السعر');
      }
      setTimeout(() => {
        if (statusEl) statusEl.textContent = '';
      }, 3500);
    }
  } catch (err) {
    console.error('Save failed:', err);
    if (statusEl) {
      statusEl.className = 'error';
      statusEl.textContent = err.message || 'تعذر الحفظ';
    }
    alert(err.message || 'تعذر حفظ عرض السعر. يرجى التحقق من المدخلات.');
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.textContent = saveBtn.dataset.originalText || (isUpdate ? i18n('update_quotation', 'تحديث عرض السعر') : i18n('save_quotation', 'حفظ عرض السعر'));
    }
  }
}



/* ===== UI events ===== */
document.getElementById('addRowBtn').addEventListener('click', () => addServiceRow());
document.getElementById('addPaymentBtn').addEventListener('click', () => addPayment({ percent: 0, amount: '', due: '' }));
document.getElementById('previewBtn').addEventListener('click', () => {
  buildPreview();
  const stage = document.querySelector('.preview-stage');
  if (window.innerWidth > 980) stage?.scrollTo({ top: 0, behavior: 'smooth' });
  else stage?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
document.getElementById('printBtn').addEventListener('click', printQuote);
document.getElementById('printTopBtn').addEventListener('click', printQuote);
document.getElementById('wordBtn').addEventListener('click', exportWord);
document.getElementById('wordTopBtn').addEventListener('click', exportWord);
document.getElementById('loadDemoBtn').addEventListener('click', loadDemo);
document.getElementById('resetBtn').addEventListener('click', resetForm);
document.getElementById('resetTypographyBtn').addEventListener('click', resetTypography);
document.getElementById('crmSaveQuotationBtn')?.addEventListener('click', saveToCrm);

els.offerType.addEventListener('change', () => applyType(els.offerType.value, true));
els.autoPaymentAmounts.addEventListener('change', () => { renderPaymentEditor(); updateAll(false); });

[
  els.clientName, els.quotationNo, els.quoteDate, els.vatRate, els.offerTitle, els.introText, els.leadText,
  els.paymentTimes, els.paymentYear, els.transportNote, els.closingText, els.showVatRow, els.formatNumbers,
  els.showTransportNote, els.showClosing, els.quoteFontFamily, els.quoteFontSize, els.quoteAccentColor,
  els.enableAccentColor, els.quoteTextColor, els.enableTextColor
].forEach(control => {
  if (!control) return;
  const handler = () => {
    if (control === els.quoteFontSize) els.quoteFontSizeValue.textContent = `${control.value}%`;
    updateAll(false);
  };
  control.addEventListener('input', handler);
  control.addEventListener('change', handler);
});

/* ===== Resizable editor pane ===== */
(function initResizableBuilder() {
  const root = document.documentElement;
  const resizer = document.getElementById('builderResizer');
  const resetLayoutBtn = document.getElementById('resetLayoutBtn');
  if (!resizer) return;
  const DEFAULT_WIDTH = 540;
  const MIN_WIDTH = 390;
  const MAX_WIDTH = 800;
  let dragging = false;

  function clamp(v) { return Math.min(MAX_WIDTH, Math.max(MIN_WIDTH, v)); }
  function applyWidth(v, save = true) {
    const width = clamp(v);
    root.style.setProperty('--builder-width', `${width}px`);
    if (save) { try { localStorage.setItem('mpc-builder-width', String(width)); } catch (_) {} }
  }
  function restore() {
    if (window.innerWidth <= 980) return;
    let saved = DEFAULT_WIDTH;
    try { saved = Number(localStorage.getItem('mpc-builder-width')) || DEFAULT_WIDTH; } catch (_) {}
    applyWidth(saved, false);
  }
  function resetWidth() { applyWidth(DEFAULT_WIDTH); }

  resizer.addEventListener('pointerdown', event => {
    if (window.innerWidth <= 980) return;
    dragging = true;
    resizer.setPointerCapture?.(event.pointerId);
    event.preventDefault();
  });
  window.addEventListener('pointermove', event => {
    if (!dragging) return;
    applyWidth(event.clientX);
    event.preventDefault();
  }, { passive: false });
  window.addEventListener('pointerup', event => {
    if (!dragging) return;
    dragging = false;
    try { resizer.releasePointerCapture?.(event.pointerId); } catch (_) {}
  });
  resizer.addEventListener('dblclick', resetWidth);
  resetLayoutBtn?.addEventListener('click', resetWidth);
  resizer.tabIndex = 0;
  resizer.addEventListener('keydown', event => {
    if (window.innerWidth <= 980) return;
    const current = parseFloat(getComputedStyle(root).getPropertyValue('--builder-width')) || DEFAULT_WIDTH;
    if (event.key === 'ArrowLeft') { applyWidth(current - 24); event.preventDefault(); }
    if (event.key === 'ArrowRight') { applyWidth(current + 24); event.preventDefault(); }
    if (event.key === 'Home') { resetWidth(); event.preventDefault(); }
  });
  window.addEventListener('resize', restore);
  restore();
})();

/* ===== Responsive A4 preview scaling ===== */
(function initResponsivePreview() {
  const root = document.documentElement;
  const stage = document.querySelector('.preview-stage');
  const preview = els.quotePreview;
  if (!stage || !preview) return;
  const A4_WIDTH_PX = (210 / 25.4) * 96;
  let rafId = 0;
  function updatePreviewZoom() {
    cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(() => {
      if (window.matchMedia?.('print').matches) { root.style.setProperty('--preview-zoom', '1'); return; }
      const styles = getComputedStyle(stage);
      const pad = (parseFloat(styles.paddingLeft) || 0) + (parseFloat(styles.paddingRight) || 0);
      const available = Math.max(240, stage.clientWidth - pad - 8);
      const scale = Math.min(1, Math.max(.28, available / A4_WIDTH_PX));
      root.style.setProperty('--preview-zoom', scale.toFixed(4));
    });
  }
  if ('ResizeObserver' in window) new ResizeObserver(updatePreviewZoom).observe(stage);
  if ('MutationObserver' in window) new MutationObserver(updatePreviewZoom).observe(preview, { childList: true });
  window.addEventListener('resize', updatePreviewZoom, { passive: true });
  window.addEventListener('beforeprint', () => root.style.setProperty('--preview-zoom', '1'));
  window.addEventListener('afterprint', updatePreviewZoom);
  updatePreviewZoom();
})();

/* Initial state / restoration */
rowsByType.other.forEach(r => { r._id = nextRowId++; });
rowsByType.iso.forEach(r => { r._id = nextRowId++; });
rowsByType.inspection.forEach(r => { r._id = nextRowId++; });
payments = defaultPaymentsFor('other');
applyType('other', true);
resetTypography();

if (window.CRM_QUOTATION_DATA && window.CRM_QUOTATION_DATA.generator === 'mpc') {
  populateFromPayload(window.CRM_QUOTATION_DATA);
} else if (window.CRM_QUOTATION_PREFILL) {
  if (window.CRM_QUOTATION_PREFILL.clientName) els.clientName.value = window.CRM_QUOTATION_PREFILL.clientName;
  if (window.CRM_QUOTATION_PREFILL.quotationNo) els.quotationNo.value = window.CRM_QUOTATION_PREFILL.quotationNo;
  if (window.CRM_QUOTATION_PREFILL.quoteDate) els.quoteDate.value = window.CRM_QUOTATION_PREFILL.quoteDate;
  updateAll(false);
}

window.MPC_SCRIPT_INITIALIZED = true;
window.exportWord = exportWord;
window.quotePageToPng = quotePageToPng;
window.saveToCrm = saveToCrm;
