import { Link } from '@inertiajs/react';

const getActivityIcon = (type) => {
    switch (type) {
        case 'created':
            return (
                <div className="flex-shrink-0">
                    <span className="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                        <svg className="h-5 w-5 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </span>
                </div>
            );
        case 'updated':
            return (
                <div className="flex-shrink-0">
                    <span className="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <svg className="h-5 w-5 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </span>
                </div>
            );
        case 'deleted':
            return (
                <div className="flex-shrink-0">
                    <span className="h-8 w-8 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                        <svg className="h-5 w-5 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </span>
                </div>
            );
        case 'checked_out':
            return (
                <div className="flex-shrink-0">
                    <span className="h-8 w-8 rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center">
                        <svg className="h-5 w-5 text-yellow-500 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </span>
                </div>
            );
        case 'checked_in':
            return (
                <div className="flex-shrink-0">
                    <span className="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                        <svg className="h-5 w-5 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>
            );
        default:
            return (
                <div className="flex-shrink-0">
                    <span className="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <svg className="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
            );
    }
};

const getActivityMessage = (activity) => {
    const { type, subject_type, subject, causer } = activity;
    const subjectName = subject?.name || subject?.title || 'an item';
    const userName = causer?.name || 'Someone';
    
    switch (type) {
        case 'created':
            return `${userName} added a new ${subject_type?.toLowerCase()}: ${subjectName}`;
        case 'updated':
            return `${userName} updated ${subject_type?.toLowerCase()}: ${subjectName}`;
        case 'deleted':
            return `${userName} deleted ${subject_type?.toLowerCase()}: ${subjectName}`;
        case 'checked_out':
            return `${userName} checked out ${subject_type?.toLowerCase()}: ${subjectName}`;
        case 'checked_in':
            return `${userName} checked in ${subject_type?.toLowerCase()}: ${subjectName}`;
        default:
            return `${userName} performed ${type} on ${subject_type?.toLowerCase()}: ${subjectName}`;
    }
};

const getActivityLink = (activity) => {
    const { subject_type, subject } = activity;
    
    if (!subject_type || !subject) return null;
    
    const type = subject_type.toLowerCase();
    const id = subject.id;
    
    switch (type) {
        case 'asset':
            return route('assets.show', id);
        case 'user':
            return route('admin.users.show', id);
        case 'maintenance':
            return route('maintenance.show', id);
        default:
            return null;
    }
};

export default function RecentActivities({ activities }) {
    if (!activities || activities.length === 0) {
        return (
            <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Recent Activities
                </h3>
                <p className="text-gray-500 dark:text-gray-400 text-center py-4">
                    No activities to display
                </p>
            </div>
        );
    }

    return (
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div className="p-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Recent Activities
                </h3>
                <div className="flow-root">
                    <ul className="-mb-8">
                        {activities.map((activity, index) => {
                            const link = getActivityLink(activity);
                            const message = getActivityMessage(activity);
                            const date = new Date(activity.created_at).toLocaleString();
                            const isLast = index === activities.length - 1;
                            
                            return (
                                <li key={activity.id}>
                                    <div className="relative pb-8">
                                        {!isLast && (
                                            <span 
                                                className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" 
                                                aria-hidden="true"
                                            />
                                        )}
                                        <div className="relative flex space-x-3">
                                            {getActivityIcon(activity.type)}
                                            <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    {link ? (
                                                        <Link 
                                                            href={link}
                                                            className="text-sm text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400"
                                                        >
                                                            {message}
                                                        </Link>
                                                    ) : (
                                                        <p className="text-sm text-gray-800 dark:text-gray-200">
                                                            {message}
                                                        </p>
                                                    )}
                                                    <p className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                                        {date}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </div>
                <div className="mt-6">
                    <Link 
                        href={route('activities.index')}
                        className="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        View all activities
                    </Link>
                </div>
            </div>
        </div>
    );
}
