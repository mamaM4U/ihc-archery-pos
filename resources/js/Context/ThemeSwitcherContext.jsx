import React, { createContext, useContext, useState, useEffect } from 'react';

const ThemeSwitcher = createContext();

export const ThemeSwitcherProvider = ({ children }) => {
    // define state darkMode with system preference detection
    const [darkMode, setDarkMode] = useState(() => {
        const localValue = localStorage.getItem('darkMode');
        if (localValue !== null) {
            return localValue === 'true';
        }
        if (typeof window !== 'undefined') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        return false;
    });

    useEffect(() => {
        const root = document.documentElement;
        const toggleTransition = () => {
            root.classList.add('no-transition');
            setTimeout(() => {
                root.classList.remove('no-transition');
            }, 0);
        };

        toggleTransition();

        if (darkMode) {
            document.body.classList.add('dark');
            document.documentElement.classList.add('dark');
        } else {
            document.body.classList.remove('dark');
            document.documentElement.classList.remove('dark');
        }

        // set darkMode in localstorage
        localStorage.setItem('darkMode', darkMode);
    }, [darkMode]);

    // Listen for system theme changes if there is no user manual preference
    useEffect(() => {
        if (typeof window === 'undefined') return;

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handleChange = (e) => {
            if (localStorage.getItem('darkMode') === null) {
                setDarkMode(e.matches);
            }
        };

        mediaQuery.addEventListener('change', handleChange);
        return () => mediaQuery.removeEventListener('change', handleChange);
    }, []);

    const themeSwitcher = () => setDarkMode(!darkMode);

    return (
        <ThemeSwitcher.Provider value={{ darkMode, themeSwitcher }}>
            {children}
        </ThemeSwitcher.Provider>
    )
}

export const useTheme = () => useContext(ThemeSwitcher);
