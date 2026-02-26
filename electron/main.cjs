const { app, BrowserWindow, globalShortcut, dialog } = require('electron')
const path = require('path')
const fs = require('fs')

const BASE_URL = 'http://localhost/almanar-market/public'
const PRINTER_NAME = 'XP-80C (copy 1)'
const PAPER_WIDTH = 72000
const LOG_FILE = path.join(app.getPath('userData'), 'print.log')

let mainWindow

function log(level, message, data) {
    const timestamp = new Date().toISOString()
    const entry = `[${timestamp}] [${level}] ${message}${data ? ' | ' + JSON.stringify(data) : ''}\n`
    try {
        fs.appendFileSync(LOG_FILE, entry)
    } catch (e) {}
    if (level === 'ERROR') {
        console.error(entry.trim())
    } else {
        console.log(entry.trim())
    }
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        show: false,
        autoHideMenuBar: true,
        icon: path.join(__dirname, '../build/icon.ico'),
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            devTools: false
        }
    })

    mainWindow.maximize()
    mainWindow.show()

    mainWindow.loadURL(BASE_URL)

    mainWindow.setMenu(null)

    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        if (url.includes('/print-thermal') || url.includes('/print?') || url.endsWith('/print')) {
            log('INFO', 'Print request intercepted', { url })
            silentPrint(url)
            return { action: 'deny' }
        }
        return { action: 'allow' }
    })

    mainWindow.webContents.on('before-input-event', (event, input) => {
        if (
            input.key === 'F12' ||
            (input.control && input.shift && input.key.toLowerCase() === 'i') ||
            (input.control && input.key.toLowerCase() === 'j')
        ) {
            event.preventDefault()
        }
    })
}

function silentPrint(url) {
    const printUrl = new URL(url)
    printUrl.searchParams.delete('auto')
    printUrl.searchParams.delete('close')

    const cleanUrl = printUrl.toString()
    log('INFO', 'Starting silent print', { url: cleanUrl, printer: PRINTER_NAME })

    const printWindow = new BrowserWindow({
        show: false,
        width: 272,
        height: 800,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true
        }
    })

    printWindow.loadURL(cleanUrl)

    printWindow.webContents.on('did-finish-load', async () => {
        log('INFO', 'Page loaded successfully', { url: cleanUrl })

        try {
            await printWindow.webContents.executeJavaScript(`
                window.print = function() {};
                window.close = function() {};
            `)
        } catch (e) {
            log('ERROR', 'Failed to override window.print', { error: e.message })
        }

        const printers = printWindow.webContents.getPrintersAsync
            ? await printWindow.webContents.getPrintersAsync()
            : printWindow.webContents.getPrinters()

        const printerNames = printers.map(p => p.name)
        const targetPrinter = printers.find(p => p.name === PRINTER_NAME)

        if (!targetPrinter) {
            log('ERROR', 'Printer not found', { target: PRINTER_NAME, available: printerNames })
            printWindow.close()
            return
        }

        log('INFO', 'Printer found, awaiting confirmation', { printer: PRINTER_NAME })

        const { response } = await dialog.showMessageBox(mainWindow, {
            type: 'question',
            buttons: ['طباعة', 'إلغاء'],
            defaultId: 0,
            cancelId: 1,
            title: 'تأكيد الطباعة',
            message: 'هل تريد طباعة الفاتورة؟',
            detail: `الطابعة: ${PRINTER_NAME}`
        })

        if (response === 1) {
            log('INFO', 'Print cancelled by user', { url: cleanUrl })
            printWindow.close()
            return
        }

        log('INFO', 'Print confirmed, sending print job', { printer: PRINTER_NAME })

        setTimeout(() => {
            printWindow.webContents.print({
                silent: true,
                printBackground: true,
                deviceName: PRINTER_NAME,
                pageSize: { width: PAPER_WIDTH, height: 500000 },
                margins: { marginType: 'none' },
                scaleFactor: 100
            }, (success, failureReason) => {
                if (success) {
                    log('INFO', 'Print job completed successfully', { url: cleanUrl })
                } else {
                    log('ERROR', 'Print job failed', { url: cleanUrl, reason: failureReason })
                }
                printWindow.close()
            })
        }, 1000)
    })

    printWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription) => {
        log('ERROR', 'Page failed to load', { url: cleanUrl, errorCode, errorDescription })
        printWindow.close()
    })
}

app.whenReady().then(() => {
    log('INFO', 'App started', { logFile: LOG_FILE })
    createWindow()

    globalShortcut.register('CommandOrControl+Shift+I', () => {})
    globalShortcut.register('F12', () => {})
})

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit()
})
