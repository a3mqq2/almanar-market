const { app, BrowserWindow, globalShortcut } = require('electron')
const path = require('path')

let mainWindow

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

    mainWindow.loadURL('http://localhost/almanar-market/public')

    mainWindow.setMenu(null)

    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        if (url.includes('/print-thermal') || url.includes('/print?') || url.endsWith('/print')) {
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

    const printWindow = new BrowserWindow({
        show: false,
        width: 226,
        height: 800,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true
        }
    })

    printWindow.loadURL(printUrl.toString())

    printWindow.webContents.on('did-finish-load', async () => {
        await printWindow.webContents.executeJavaScript(`
            window.print = function() {};
            window.close = function() {};
        `)

        setTimeout(async () => {
            try {
                const printers = await printWindow.webContents.getPrintersAsync()
                const defaultPrinter = printers.find(p => p.isDefault)

                if (!defaultPrinter) {
                    printWindow.close()
                    return
                }

                printWindow.webContents.print({
                    silent: true,
                    printBackground: true,
                    deviceName: defaultPrinter.name,
                    margins: { marginType: 'none' }
                }, (success) => {
                    printWindow.close()
                })
            } catch (e) {
                printWindow.close()
            }
        }, 1500)
    })

    printWindow.webContents.on('did-fail-load', () => {
        printWindow.close()
    })
}

app.whenReady().then(() => {
    createWindow()

    globalShortcut.register('CommandOrControl+Shift+I', () => {})
    globalShortcut.register('F12', () => {})
})

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit()
})
