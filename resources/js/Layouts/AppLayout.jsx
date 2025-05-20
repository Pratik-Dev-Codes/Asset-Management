import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Toaster } from 'react-hot-toast';
import Sidebar from '@/Components/Sidebar';
import Header from '@/Components/Header';
import { usePage } from '@inertiajs/react';

export default function AppLayout({ title, children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { auth } = usePage().props;

    // Close sidebar when route changes
    useEffect(() => {
        setSidebarOpen(false);
    }, [window.location.pathname]);

    return (
        <div className="min-h-screen bg-gray-50">
            <Head>
                <title>{title || 'NEEPCO Asset Management'}</title>
                <meta name="description" content="NEEPCO Asset Management System" />
                <link rel="icon" href="/favicon.ico" />
            </Head>

            <Sidebar sidebarOpen={sidebarOpen} setSidebarOpen={setSidebarOpen} />
            
            <div className="lg:pl-64 flex flex-col flex-1">
                <Header setSidebarOpen={setSidebarOpen} user={auth.user} />
                
                <main className="flex-1 pb-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        {children}
                    </div>
                </main>
            </div>
            
            <Toaster position="bottom-right" />
        </div>
    );
}
