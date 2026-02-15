const { app, BrowserWindow, dialog } = require('electron')
const { spawn } = require('child_process')
const path = require('path')
const fs = require('fs')
const net = require('net')

let phpProcess = null
let schedulerProcess = null
let mainWindow = null
const PORT = 3000

function getProjectPath(relativePath = '') {
    if (app.isPackaged) {
        return path.join(process.resourcesPath, relativePath)
    }
    return path.join(__dirname, '..', relativePath)
}

function getPhpPath() {
    const xamppPhp = 'C:\\xampp\\php\\php.exe'

    if (fs.existsSync(xamppPhp)) {
        return xamppPhp
    }

    const bundledPhp = getProjectPath('php/php.exe')

    if (fs.existsSync(bundledPhp)) {
        return bundledPhp
    }

    return 'php'
}

function isPortAvailable(port) {
    return new Promise(resolve => {
        const server = net.createServer()

        server.once('error', () => resolve(false))

        server.once('listening', () => {
            server.close()
            resolve(true)
        })

        server.listen(port, '127.0.0.1')
    })
}

async function waitForServer(maxAttempts = 40) {
    for (let i = 0; i < maxAttempts; i++) {
        const available = await isPortAvailable(PORT)

        if (!available) {
            return true
        }

        await new Promise(r => setTimeout(r, 500))
    }

    return false
}

async function startLaravel() {
    const phpPath = getPhpPath()
    const artisanPath = getProjectPath('artisan')
    const appPath = getProjectPath()

    if (!fs.existsSync(phpPath) && phpPath !== 'php') {
        dialog.showErrorBox('Error', 'PHP not found: ' + phpPath)
        app.quit()
        return false
    }

    if (!fs.existsSync(artisanPath)) {
        dialog.showErrorBox('Error', 'artisan not found: ' + artisanPath)
        app.quit()
        return false
    }

    let phpErrors = ''
    let phpExited = false

    phpProcess = spawn(
        phpPath,
        ['artisan', 'serve', '--host=127.0.0.1', '--port=' + PORT],
        {
            cwd: appPath,
            env: process.env,
            windowsHide: true
        }
    )

    phpProcess.stdout.on('data', data => {
        console.log('PHP:', data.toString())
    })

    phpProcess.stderr.on('data', data => {
        const msg = data.toString()
        console.error('PHP ERR:', msg)
        phpErrors += msg + '\n'
    })

    phpProcess.on('error', err => {
        phpExited = true
        dialog.showErrorBox('PHP Error', 'Could not start PHP: ' + err.message + '\nPath: ' + phpPath)
        app.quit()
    })

    phpProcess.on('exit', (code) => {
        if (code !== null && code !== 0) {
            phpExited = true
        }
    })

    const serverReady = await waitForServer()

    if (!serverReady && phpExited) {
        dialog.showErrorBox('Server Error', 'PHP exited with errors:\n\n' + (phpErrors || 'Unknown error') + '\n\nPHP: ' + phpPath + '\nCWD: ' + appPath)
        app.quit()
        return false
    }

    if (!serverReady) {
        dialog.showErrorBox('Server Error', 'Server did not start in time.\n\nPHP: ' + phpPath + '\nArtisan: ' + artisanPath + '\nPort: ' + PORT + '\n\n' + (phpErrors || 'No errors captured'))
        app.quit()
        return false
    }

    return true
}

function startScheduler() {
    const phpPath = getPhpPath()
    const appPath = getProjectPath()

    schedulerProcess = spawn(
        phpPath,
        ['artisan', 'schedule:work'],
        {
            cwd: appPath,
            env: process.env,
            windowsHide: true
        }
    )

    schedulerProcess.stdout.on('data', data => {
        console.log('Scheduler:', data.toString())
    })

    schedulerProcess.stderr.on('data', data => {
        console.error('Scheduler ERR:', data.toString())
    })

    schedulerProcess.on('error', err => {
        console.error('Scheduler failed:', err.message)
    })
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
            devTools: !app.isPackaged
        }
    })

    mainWindow.loadURL('http://127.0.0.1:' + PORT)

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

    loading.close()

    if (serverReady) {
        createWindow()
        startScheduler()
    }
})

function killProcesses() {
    if (schedulerProcess) {
        schedulerProcess.kill()
        schedulerProcess = null
    }
    if (phpProcess) {
        phpProcess.kill()
        phpProcess = null
    }
}

app.on('window-all-closed', () => {
    killProcesses()
    app.quit()
})

app.on('before-quit', () => {
    killProcesses()
})

app.on('activate', () => {
    if (mainWindow === null && app.isReady()) {
        createWindow()
    }
})