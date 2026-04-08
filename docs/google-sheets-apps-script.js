const SHEET_NAME = 'Transactions';
const TOKEN = 'ganti-dengan-token-rahasia';

function doPost(e) {
  try {
    const body = JSON.parse(e.postData.contents || '{}');
    const token = (e.parameter.token || '') || (e.postData && e.postData.type ? '' : '');
    const headerToken = e?.headers?.['X-LYVA-SHEETS-TOKEN'] || e?.headers?.['x-lyva-sheets-token'] || '';

    if (TOKEN && headerToken !== TOKEN && token !== TOKEN) {
      return jsonResponse({ ok: false, message: 'Unauthorized' }, 401);
    }

    const sheet = getSheet_();
    const tx = body.transaction || {};
    const publicId = String(tx.publicId || '').trim();

    if (!publicId) {
      return jsonResponse({ ok: false, message: 'Missing publicId' }, 422);
    }

    const headers = headerRow_();
    const row = [
      new Date(),
      body.event || '',
      tx.publicId || '',
      tx.status || '',
      tx.paymentStatus || '',
      tx.productSource || '',
      tx.productId || '',
      tx.productName || '',
      tx.packageLabel || '',
      tx.quantity || 1,
      tx.subtotal || 0,
      tx.promoCode || '',
      tx.promoDiscount || 0,
      tx.total || 0,
      tx.paymentMethodLabel || '',
      tx.customerName || '',
      tx.customerEmail || '',
      tx.customerWhatsapp || '',
      tx.accountSummary || '',
      tx.contactSummary || '',
      tx.fulfillmentNote || '',
      tx.errorMessage || '',
      tx.ratingScore || '',
      tx.ratingComment || '',
      tx.createdAt || '',
      tx.updatedAt || '',
      tx.paidAt || '',
      tx.fulfilledAt || '',
      tx.ratedAt || '',
    ];

    const rowIndex = findRowByPublicId_(sheet, publicId);

    if (rowIndex > 1) {
      sheet.getRange(rowIndex, 1, 1, row.length).setValues([row]);
    } else {
      ensureHeaders_(sheet, headers);
      sheet.appendRow(row);
    }

    return jsonResponse({ ok: true, publicId }, 200);
  } catch (error) {
    return jsonResponse({ ok: false, message: String(error) }, 500);
  }
}

function getSheet_() {
  const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = spreadsheet.getSheetByName(SHEET_NAME);

  if (!sheet) {
    sheet = spreadsheet.insertSheet(SHEET_NAME);
  }

  ensureHeaders_(sheet, headerRow_());

  return sheet;
}

function ensureHeaders_(sheet, headers) {
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(headers);
    return;
  }

  const current = sheet.getRange(1, 1, 1, headers.length).getValues()[0];
  const matches = headers.every((header, index) => current[index] === header);

  if (!matches) {
    sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
  }
}

function findRowByPublicId_(sheet, publicId) {
  const lastRow = sheet.getLastRow();

  if (lastRow < 2) {
    return -1;
  }

  const values = sheet.getRange(2, 3, lastRow - 1, 1).getValues();

  for (let index = 0; index < values.length; index += 1) {
    if (String(values[index][0]) === publicId) {
      return index + 2;
    }
  }

  return -1;
}

function headerRow_() {
  return [
    'Synced At',
    'Event',
    'Public ID',
    'Status',
    'Payment Status',
    'Product Source',
    'Product ID',
    'Product Name',
    'Package Label',
    'Quantity',
    'Subtotal',
    'Promo Code',
    'Promo Discount',
    'Total',
    'Payment Method',
    'Customer Name',
    'Customer Email',
    'Customer WhatsApp',
    'Account Summary',
    'Contact Summary',
    'Fulfillment Note',
    'Error Message',
    'Rating Score',
    'Rating Comment',
    'Created At',
    'Updated At',
    'Paid At',
    'Fulfilled At',
    'Rated At',
  ];
}

function jsonResponse(payload, status) {
  return ContentService
    .createTextOutput(JSON.stringify({ status, ...payload }))
    .setMimeType(ContentService.MimeType.JSON);
}
