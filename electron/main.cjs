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

    mainWindow.loadURL('http://127.0.0.1:8000')

    mainWindow.setMenu(null)

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

app.whenReady().then(() => {
    createWindow()

    globalShortcut.register('CommandOrControl+Shift+I', () => {})
    globalShortcut.register('F12', () => {})
})

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit()
})
