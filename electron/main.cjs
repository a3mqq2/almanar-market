const { app, BrowserWindow, dialog } = require('electron')
const { spawn } = require('child_process')
const path = require('path')
const fs = require('fs')
const net = require('net')

let phpProcess = null
let mainWindow = null
const PORT = 8000

function getResourcePath(relativePath) {
    if (app.isPackaged) {
        return path.join(process.resourcesPath, relativePath)
    }
    return path.join(__dirname, '..', relativePath)
}

function getPhpPath() {
    const phpPath = getResourcePath('php/php.exe')
    if (fs.existsSync(phpPath)) {
        return phpPath
    }
    return 'php'
}

function isPortAvailable(port) {
    return new Promise((resolve) => {
        const server = net.createServer()
        server.once('error', () => resolve(false))
        server.once('listening', () => {
            server.close()
            resolve(true)
        })
        server.listen(port, '127.0.0.1')
    })
}

async function waitForServer(maxAttempts = 30) {
    for (let i = 0; i < maxAttempts; i++) {
        try {
            const available = await isPortAvailable(PORT)
            if (!available) {
                return true
            }
        } catch (e) {}
        await new Promise(r => setTimeout(r, 500))
    }
    return false
}

async function startLaravel() {
    const phpPath = getPhpPath()
    const artisanPath = getResourcePath('artisan')
    const appPath = getResourcePath('')

    if (!fs.existsSync(artisanPath)) {
        dialog.showErrorBox('Error', 'Laravel artisan not found: ' + artisanPath)
        app.quit()
        return false
    }

    phpProcess = spawn(phpPath, ['artisan', 'serve', '--host=127.0.0.1', '--port=' + PORT], {
        cwd: appPath,
        env: { ...process.env, APP_ENV: 'local' }
    })

    phpProcess.stdout.on('data', (data) => {
        console.log('PHP: ' + data)
    })

    phpProcess.stderr.on('data', (data) => {
        console.error('PHP Error: ' + data)
    })

    phpProcess.on('error', (err) => {
        dialog.showErrorBox('PHP Error', 'Failed to start PHP: ' + err.message)
        app.quit()
    })

    return await waitForServer()
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 1024,
        minHeight: 768,
        autoHideMenuBar: true,
        icon: path.join(__dirname, '../build/icon.ico'),
        webPreferences: {
            contextIsolation: true,
            nodeIntegration: false,
            devTools: false
        }
    })

    mainWindow.loadURL('http://127.0.0.1:' + PORT).then(() => {
        if (!app.isPackaged) {
            mainWindow.webContents.openDevTools()
        }
    })

    mainWindow.on('closed', () => {
        mainWindow = null
    })
}


function createLoadingWindow() {
    const loading = new BrowserWindow({
        width: 400,
        height: 300,
        frame: false,
        transparent: true,
        alwaysOnTop: true,
        webPreferences: {
            contextIsolation: true
        }
    })

    loading.loadFile(path.join(__dirname, 'loading.html'))
    return loading
}

app.whenReady().then(async () => {
    const loading = createLoadingWindow()

    const serverReady = await startLaravel()

    if (serverReady) {
        loading.close()
        createWindow()
    } else {
        loading.close()
        dialog.showErrorBox('Error', 'Failed to start the server')
        app.quit()
    }
})

app.on('window-all-closed', () => {
    if (phpProcess) {
        phpProcess.kill()
        phpProcess = null
    }
    app.quit()
})

app.on('before-quit', () => {
    if (phpProcess) {
        phpProcess.kill()
        phpProcess = null
    }
})

app.on('activate', () => {
    if (mainWindow === null && app.isReady()) {
        createWindow()
    }
})
