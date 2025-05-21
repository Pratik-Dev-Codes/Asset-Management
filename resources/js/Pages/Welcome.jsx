import { Link, Head } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">
                <div className="sm:fixed sm:top-0 sm:right-0 p-6 text-end">
                    {auth.user ? (
                        <Link
                            href={route('dashboard')}
                            className="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                        >
                            Dashboard
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={route('login')}
                                className="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                            >
                                Log in
                            </Link>

                            <Link
                                href={route('register')}
                                className="ms-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                            >
                                Register
                            </Link>
                        </>
                    )}
                </div>

                <div className="max-w-7xl mx-auto p-6 lg:p-8 text-center">
                    <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">
                        Welcome to {window.appName || 'Asset Management System For NEEPCO LTD'}
                    </h1>
                    <p className="text-lg text-gray-600 dark:text-gray-300 mb-8">
                        A comprehensive solution for managing NEEPCO's organizational assets
                    </p>
                    <div className="flex justify-center mt-8">
                        <img
                            src="/images/neepco-logo.png"
                            alt="NEEPCO Logo"
                            className="h-32 w-auto"
                            onError={(e) => {
                                e.target.style.display = 'none';
                            }}
                        />
                        <svg className="h-32 w-auto" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.99677 9.83281L12.013 4.03558C12.0255 4.02827 12.038 4.02109 12.0505 4.01403C12.0629 4.00684 12.0754 4 12.088 4C12.1005 4 12.113 4.00684 12.1255 4.01403C12.138 4.02109 12.1505 4.02827 12.163 4.03558L22.1792 9.83281C22.2042 9.84756 22.2276 9.86558 22.25 9.88672C22.2724 9.90787 22.2933 9.93125 22.313 9.95586L12.088 15.5801L1.86304 9.95586C1.88269 9.93125 1.90361 9.90787 1.92599 9.88672C1.94837 9.86558 1.9718 9.84756 1.99677 9.83281ZM1.99677 12.2446V42.3569C1.99677 42.3943 1.98677 42.4317 1.98177 42.4691C1.9769 42.5177 1.97437 42.5688 1.9619 42.6161C1.9519 42.656 1.93065 42.6909 1.91448 42.7283C1.8983 42.7707 1.8883 42.8156 1.86304 42.8529C1.8433 42.8822 1.81565 42.9056 1.79327 42.9312C1.7709 42.9568 1.75277 42.9859 1.7319 43.0125C1.71572 43.0328 1.6948 43.05 1.67742 43.0687C1.66004 43.0875 1.6464 43.1094 1.62764 43.1266C1.49677 43.2687 1.3319 43.3711 1.15264 43.4281C1.10361 43.4437 1.05264 43.45 1.00264 43.45C0.952639 43.45 0.90177 43.4437 0.852639 43.4281C0.6733 43.3711 0.508424 43.2687 0.377639 43.1266C0.358861 43.1094 0.34521 43.0875 0.327826 43.0687C0.310444 43.05 0.289674 43.0328 0.273424 43.0125C0.252556 42.9859 0.234424 42.9568 0.212046 42.9312C0.189671 42.9056 0.162046 42.8822 0.142296 42.8529C0.117046 42.8156 0.107046 42.7707 0.0908606 42.7283C0.0746731 42.6909 0.0534231 42.656 0.0434231 42.6161C0.0309531 42.5688 0.0284231 42.5177 0.0234869 42.4691C0.0185506 42.4317 0.00855062 42.3943 0.00855064 42.3569V15.5801L6.18848 12.2446L1.99677 9.83281ZM12.9777 2.36177L2.99764 8.10652L12.9752 13.8513L22.9541 8.10527L12.9752 2.36177H12.9777ZM18.1678 38.2138L23.9574 34.8809V9.83281L19.7657 12.2459L13.9749 15.5801V40.6281L18.1678 38.2138ZM48.9133 9.14105L38.9344 14.8858L48.9133 20.6305L58.8909 14.8846L48.9133 9.14105ZM47.9149 22.3593L42.124 19.0252L37.9323 16.6121V27.9844L43.7219 31.3174L47.9149 33.7317V22.3593ZM24.9533 47.987L39.59 39.631L46.9065 35.4555L36.9352 29.7145L25.4544 36.3242L14.9907 42.3482L24.9533 47.987Z" fill="#FF2D20"/>
                        </svg>
                    </div>

                    <div className="mt-16">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                            <a
                                href="https://laravel.com/docs"
                                className="scale-100 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500"
                            >
                                <div>
                                    <div className="h-16 w-16 bg-red-50 dark:bg-red-800/20 flex items-center justify-center rounded-full">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            strokeWidth="1.5"
                                            className="w-7 h-7 stroke-red-500"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
                                            />
                                        </svg>
                                    </div>

                                    <h2 className="mt-6 text-xl font-semibold text-gray-900 dark:text-white">
                                        Documentation
                                    </h2>

                                    <p className="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                                        Laravel has wonderful documentation covering every aspect of the framework.
                                        Whether you are a newcomer or have prior experience with Laravel, we recommend
                                        reading our documentation from beginning to end.
                                    </p>
                                </div>

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth="1.5"
                                    className="self-center shrink-0 stroke-red-500 w-6 h-6 mx-6"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"
                                    />
                                </svg>
                            </a>

                            <a
                                href="https://laracasts.com"
                                className="scale-100 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500"
                            >
                                <div>
                                    <div className="h-16 w-16 bg-red-50 dark:bg-red-800/20 flex items-center justify-center rounded-full">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            strokeWidth="1.5"
                                            className="w-7 h-7 stroke-red-500"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"
                                            />
                                        </svg>
                                    </div>

                                    <h2 className="mt-6 text-xl font-semibold text-gray-900 dark:text-white">
                                        Laracasts
                                    </h2>

                                    <p className="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                                        Laracasts offers thousands of video tutorials on Laravel, PHP, and JavaScript
                                        development. Check them out, see for yourself, and massively level up your
                                        development skills in the process.
                                    </p>
                                </div>

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth="1.5"
                                    className="self-center shrink-0 stroke-red-500 w-6 h-6 mx-6"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"
                                    />
                                </svg>
                            </a>

                            <a
                                href="https://laravel-news.com"
                                className="scale-100 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500"
                            >
                                <div>
                                    <div className="h-16 w-16 bg-red-50 dark:bg-red-800/20 flex items-center justify-center rounded-full">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            strokeWidth="1.5"
                                            className="w-7 h-7 stroke-red-500"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"
                                            />
                                        </svg>
                                    </div>

                                    <h2 className="mt-6 text-xl font-semibold text-gray-900 dark:text-white">
                                        Laravel News
                                    </h2>

                                    <p className="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                                        Laravel News is a community driven portal and newsletter aggregating all of the
                                        latest and most important news in the Laravel ecosystem, including new package
                                        releases and tutorials.
                                    </p>
                                </div>

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth="1.5"
                                    className="self-center shrink-0 stroke-red-500 w-6 h-6 mx-6"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"
                                    />
                                </svg>
                            </a>

                            <div className="scale-100 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none flex motion-safe:hover:scale-[1.01] transition-all duration-250 focus:outline focus:outline-2 focus:outline-red-500">
                                <div>
                                    <div className="h-16 w-16 bg-red-50 dark:bg-red-800/20 flex items-center justify-center rounded-full">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            strokeWidth="1.5"
                                            className="w-7 h-7 stroke-red-500"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M6.115 5.19l.319 1.913A6 6 0 008.11 10.36L9.75 12l-.387.775c-.217.433-.132.956.21 1.298l1.348 1.348c.21.21.329.497.329.795v1.089c0 .426.24.815.622 1.006l.153.076c.433.217.956.132 1.298-.21l.723-.723a8.7 8.7 0 002.288-4.042 1.087 1.087 0 00-.358-1.099l-1.33-1.108c-.251-.21-.582-.299-.905-.245l-1.17.195a1.125 1.125 0 01-.98-.314l-.295-.295a1.125 1.125 0 010-1.591l.13-.132a1.125 1.125 0 011.3-.21l.603.302a.809.809 0 001.086-1.086L14.25 7.5l1.256-.837a4.5 4.5 0 001.528-1.732l.146-.292M6.115 5.19A9 9 0 1017.18 4.64M6.115 5.19A8.965 8.965 0 0112 3c1.929 0 3.716.607 5.18 1.64"
                                            />
                                        </svg>
                                    </div>

                                    <h2 className="mt-6 text-xl font-semibold text-gray-900 dark:text-white">
                                        Vibrant Ecosystem
                                    </h2>

                                    <p className="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                                        Laravel's robust library of first-party tools and libraries, such as{' '}
                                        <a
                                            href="https://forge.laravel.com"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Forge
                                        </a>
                                        ,{' '}
                                        <a
                                            href="https://vapor.laravel.com"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Vapor
                                        </a>
                                        ,{' '}
                                        <a
                                            href="https://nova.laravel.com"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Nova
                                        </a>
                                        , and{' '}
                                        <a
                                            href="https://envoyer.io"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Envoyer
                                        </a>{' '}
                                        help you take your projects to the next level. Pair them with powerful open
                                        source libraries like{' '}
                                        <a
                                            href="https://laravel.com/docs/billing"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Cashier
                                        </a>
                                        ,{' '}
                                        <a
                                            href="https://laravel.com/docs/dusk"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Dusk
                                        </a>
                                        ,{' '}
                                        <a
                                            href="https://laravel.com/docs/broadcasting"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Echo
                                        </a>
                                        ,{' '}
                                        <a
                                            href="https://laravel.com/docs/horizon"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Horizon
                                        </a>
                                        ,{' '}
                                        <a
                                            href="https://laravel.com/docs/sanctum"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Sanctum
                                        </a>
                                        ,{' '}
                                        <a
                                            href="https://laravel.com/docs/telescope"
                                            className="underline hover:text-gray-700 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                                        >
                                            Telescope
                                        </a>
                                        , and more.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-center mt-16 px-6 sm:items-center sm:justify-between">
                        <div className="text-center text-sm sm:text-start">&nbsp;</div>

                        <div className="text-center text-sm text-gray-500 dark:text-gray-400 sm:text-end sm:ms-0">
                            Laravel v{laravelVersion} (PHP v{phpVersion})
                        </div>
                    </div>
                </div>
            </div>

            <style>{`
                .bg-dots-darker {
                    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
                }
                @media (prefers-color-scheme: dark) {
                    .dark\\:bg-dots-lighter {
                        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
                    }
                }
            `}</style>
        </>
    );
}
