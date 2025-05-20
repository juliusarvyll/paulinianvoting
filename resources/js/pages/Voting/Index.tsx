import { Head, useForm } from '@inertiajs/react';
import { useAppearance } from '@/hooks/use-appearance';
import { useState } from 'react';

type VoteData = {
    votes: number[];
};

interface Candidate {
    id: number;
    voter: {
        id: number;
        first_name: string;
        last_name: string;
        middle_name: string;
    };
    slogan: string;
    photo_path: string | null;
}

interface Position {
    id: number;
    name: string;
    max_winners: number;
    level: 'university' | 'department' | 'course' | 'year_level';
    candidates: Candidate[];
}

interface VoteFormData {
    votes: VoteSubmission[];
    _token?: string;
    voter_id?: number;
    [key: string]: any;
}

// Add a new interface to better match the Vote model structure
interface VoteSubmission {
    candidate_id: number;
    position_id: number;
    voter_id?: number;
    election_id?: number;
}

interface Props {
    voter: {
        id: number;
        code: string;
        first_name: string;
        last_name: string;
        middle_name: string;
        course: {
            id: number;
            course_name: string;
            department: {
                id: number;
                name: string;
            };
        };
        year_level: number;
    };
    election: {
        id: number;
        name: string;
        start_at: string;
        end_at: string;
    };
    positions: {
        university: Position[];
        department: Position[];
        course: Position[];
        year_level: Position[];
    };
}

export default function VotingIndex({ voter, election, positions }: Props) {
    const { appearance, updateAppearance } = useAppearance();
    const [selectedCandidates, setSelectedCandidates] = useState<Record<number, number>>({});
    const { data, setData, post, processing, errors: formErrors } = useForm<VoteFormData>({
        votes: [],
        voter_id: voter.id,
    });

    const toggleTheme = () => {
        updateAppearance(appearance === 'dark' ? 'light' : 'dark');
    };

    const handleVoteChange = (positionId: number, candidateId: number) => {
        setSelectedCandidates((prev) => {
            // If the same candidate is clicked again, remove the selection
            if (prev[positionId] === candidateId) {
                const newSelected = { ...prev };
                delete newSelected[positionId];
                return newSelected;
            }
            // Otherwise, update the selection
            return {
                ...prev,
                [positionId]: candidateId,
            };
        });

        // Update form data whenever votes change
        const updatedSelectedCandidates = { ...selectedCandidates };
        if (updatedSelectedCandidates[positionId] === candidateId) {
            delete updatedSelectedCandidates[positionId];
        } else {
            updatedSelectedCandidates[positionId] = candidateId;
        }

        const formattedVotes = Object.entries(updatedSelectedCandidates).map(([posId, candId]) => {
            return {
                position_id: parseInt(posId),
                candidate_id: candId
            };
        });

        setData('votes', formattedVotes);
    };

    const handleSubmit = () => {
        // Format votes with just position_id and candidate_id
        // voter_id is already in the form data
        const formattedVotes = Object.entries(selectedCandidates).map(([positionId, candidateId]) => {
            return {
                position_id: parseInt(positionId),
                candidate_id: candidateId
            };
        });

        // Log for debugging
        console.log('Formatted votes:', formattedVotes);

        // Update the votes array in the form data
        setData('votes', formattedVotes);

        // Log what will be submitted
        console.log('Full form data to be submitted:', {
            votes: formattedVotes,
            voter_id: voter.id
        });

        // Post to the route with the proper options
        post(route('voter.cast-vote'), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Vote cast successfully');
            },
            onError: (errors: Record<string, string>) => {
                console.error('Error casting vote:', errors);
            }
        });
    };

    const renderCandidateCard = (candidate: Candidate, position: Position, isSelected: boolean) => (
        <div
            key={candidate.id}
            className={`cursor-pointer rounded-lg border p-4 transition-all ${
                isSelected
                    ? 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-900'
                    : 'border-gray-200 hover:border-indigo-300 dark:border-gray-700 dark:hover:border-indigo-600'
            }`}
            onClick={() => handleVoteChange(position.id, candidate.id)}
        >
            <div className="flex items-center gap-4">
                <div className="h-16 w-16 flex-shrink-0 overflow-hidden rounded-full">
                    {candidate.photo_path ? (
                        <img
                            src={candidate.photo_path}
                            alt={`${candidate.voter.first_name} ${candidate.voter.last_name}`}
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center bg-gray-200 dark:bg-gray-700">
                            <span className="text-2xl font-bold text-gray-500 dark:text-gray-400">
                                {candidate.voter.first_name[0]}
                                {candidate.voter.last_name[0]}
                            </span>
                        </div>
                    )}
                </div>
                <div className="flex-1">
                    <h4 className="font-medium text-gray-900 dark:text-white">
                        {candidate.voter.last_name}, {candidate.voter.first_name} {candidate.voter.middle_name}
                    </h4>
                    {candidate.slogan && (
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{candidate.slogan}</p>
                    )}
                </div>
                <div className="flex-shrink-0">
                    <div
                        className={`h-5 w-5 rounded-full border ${
                            isSelected
                                ? 'border-indigo-500 bg-indigo-500 dark:border-indigo-400 dark:bg-indigo-400'
                                : 'border-gray-300 dark:border-gray-600'
                        }`}
                    >
                        {isSelected && (
                            <svg
                                className="h-5 w-5 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );

    const renderPositionSection = (title: string, positionList: Position[]) => (
        <div className="mb-8 rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
            <h3 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">{title}</h3>
            <div className="space-y-6">
                {positionList.map((position) => (
                    <div key={position.id} className="space-y-4">
                        <h4 className="text-lg font-medium text-gray-900 dark:text-white">
                            {position.name}
                            <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                (Select {position.max_winners})
                            </span>
                        </h4>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {position.candidates.map((candidate) =>
                                renderCandidateCard(
                                    candidate,
                                    position,
                                    selectedCandidates[position.id] === candidate.id
                                )
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );

    return (
        <>
            <Head title="Vote" />
            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
                {/* Header */}
                <header className="border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg">
                                <img src="/images/spup-logo.png" alt="SPUP Logo" />
                            </div>
                            <div>
                                <h1 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    {election.name}
                                </h1>
                                <p className="text-sm text-gray-600 dark:text-gray-300">Welcome, {voter.first_name}</p>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            <button
                                onClick={toggleTheme}
                                className="rounded-full p-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                                aria-label="Toggle theme"
                            >
                                {appearance === 'dark' ? (
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-[#EDEDEC]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                ) : (
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-[#1b1b18]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                )}
                            </button>
                            <form action={route('voter.logout')} method="POST">
                                <button
                                    type="submit"
                                    className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                >
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                {/* Main Content */}
                <main className="flex-1 px-4 py-8">
                    <div className="mx-auto max-w-7xl">
                        {/* Voter Information */}
                        <div className="mb-8 rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                            <h2 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Voter Information</h2>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Student ID:</p>
                                    <p className="font-medium text-gray-900 dark:text-white">{voter.code}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Name:</p>
                                    <p className="font-medium text-gray-900 dark:text-white">
                                        {voter.last_name}, {voter.first_name} {voter.middle_name}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Course:</p>
                                    <p className="font-medium text-gray-900 dark:text-white">{voter.course.course_name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Department:</p>
                                    <p className="font-medium text-gray-900 dark:text-white">{voter.course.department.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Year Level:</p>
                                    <p className="font-medium text-gray-900 dark:text-white">{voter.year_level}</p>
                                </div>
                            </div>
                        </div>

                        {/* Voting Sections */}
                        {renderPositionSection('University Wide Positions', positions.university)}
                        {renderPositionSection('Department Wide Positions', positions.department)}
                        {renderPositionSection('Course Wide Positions', positions.course)}
                        {renderPositionSection(`Year ${voter.year_level} Positions`, positions.year_level)}

                        {/* Submit Button */}
                        <div className="mt-8 flex justify-end">
                            <button
                                onClick={handleSubmit}
                                disabled={processing}
                                className="rounded-md bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                {processing ? 'Submitting...' : 'Submit Vote'}
                            </button>
                        </div>

                        {/* Error Messages */}
                        {formErrors.votes && (
                            <div className="mt-4 rounded-md bg-red-50 p-4 dark:bg-red-900">
                                <p className="text-sm text-red-700 dark:text-red-200">{formErrors.votes}</p>
                            </div>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
